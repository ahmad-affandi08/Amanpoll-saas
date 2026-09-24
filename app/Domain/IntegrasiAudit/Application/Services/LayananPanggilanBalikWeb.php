<?php

declare(strict_types=1);

namespace App\Domain\IntegrasiAudit\Application\Services;

use App\Domain\IntegrasiAudit\Domain\Enums\StatusPengirimanPanggilanBalikWeb;
use App\Domain\IntegrasiAudit\Infrastructure\Persistence\Models\KotakKeluarPeristiwa;
use App\Domain\IntegrasiAudit\Infrastructure\Persistence\Models\PanggilanBalikWeb;
use App\Domain\IntegrasiAudit\Infrastructure\Persistence\Models\PengirimanPanggilanBalikWeb;
use App\Domain\IntegrasiAudit\Jobs\KirimPanggilanBalikWeb;
use App\Shared\Infrastructure\Keamanan\PenjagaUrlKeluar;
use App\Shared\Infrastructure\Keamanan\UrlKeluarDitolak;
use Illuminate\Http\Client\Response;

/** Menerbitkan peristiwa kotak keluar ke endpoint webhook yang berlangganan. */
final class LayananPanggilanBalikWeb
{
    public const HEADER_TANDA_TANGAN = 'X-Amanpoll-Signature';

    public const HEADER_PERISTIWA = 'X-Amanpoll-Event';

    public const HEADER_PENGIRIMAN = 'X-Amanpoll-Delivery';

    /** Panjang maksimum cuplikan balasan endpoint yang disimpan dan ditampilkan ke tenant. */
    public const BATAS_CUPLIKAN_RESPONS = 500;

    public function __construct(private readonly PenjagaUrlKeluar $penjaga) {}

    /**
     * Membuat baris pengiriman untuk tiap webhook aktif yang berlangganan.
     * Pengiriman yang sudah pernah dibuat untuk pasangan peristiwa dan webhook
     * yang sama tidak dibuat ulang, sehingga pemrosesan outbox aman diulang.
     *
     * @return list<PengirimanPanggilanBalikWeb>
     */
    public function terbitkan(KotakKeluarPeristiwa $peristiwa): array
    {
        if ($peristiwa->OrganisasiId === null) {
            return [];
        }

        $daftarWebhook = PanggilanBalikWeb::query()
            ->withoutGlobalScopes()
            ->where('OrganisasiId', $peristiwa->OrganisasiId)
            ->where('Aktif', true)
            ->get()
            ->filter(fn (PanggilanBalikWeb $webhook): bool => $this->berlangganan($webhook, $peristiwa->NamaPeristiwa));

        $dibuat = [];
        foreach ($daftarWebhook as $webhook) {
            $muatan = $this->muatan($peristiwa);

            $sudahAda = PengirimanPanggilanBalikWeb::query()
                ->withoutGlobalScopes()
                ->where('PanggilanBalikWebId', $webhook->Id)
                ->where('Peristiwa', $peristiwa->NamaPeristiwa)
                ->whereJsonContains('MuatanData->IdPeristiwa', $peristiwa->Id)
                ->exists();

            if ($sudahAda) {
                continue;
            }

            $pengiriman = PengirimanPanggilanBalikWeb::create([
                'OrganisasiId' => $peristiwa->OrganisasiId,
                'PanggilanBalikWebId' => $webhook->Id,
                'Peristiwa' => $peristiwa->NamaPeristiwa,
                'MuatanData' => $muatan,
                'Status' => StatusPengirimanPanggilanBalikWeb::Antri->value,
            ]);

            KirimPanggilanBalikWeb::dispatch($pengiriman->Id);
            $dibuat[] = $pengiriman;
        }

        return $dibuat;
    }

    /** Mengirim satu pengiriman ke endpoint tujuan dan mencatat hasilnya. */
    public function kirim(PengirimanPanggilanBalikWeb $pengiriman): bool
    {
        if ($pengiriman->Status === StatusPengirimanPanggilanBalikWeb::Berhasil->value) {
            return true;
        }

        $webhook = PanggilanBalikWeb::query()->withoutGlobalScopes()->whereKey($pengiriman->PanggilanBalikWebId)->first();
        if (! $webhook instanceof PanggilanBalikWeb || ! $webhook->Aktif) {
            $this->tandaiGagalPermanen($pengiriman, 'Webhook tujuan tidak aktif atau sudah dihapus.');

            return false;
        }

        // Diperiksa ulang tiap kirim: URL lolos saat disimpan, tetapi DNS-nya dapat
        // diarahkan ke jaringan internal sesudahnya. Yang ditolak tidak pernah dikirim.
        try {
            $tujuan = $this->penjaga->periksa((string) $webhook->Url);
            $klien = $this->penjaga->klien($tujuan);
        } catch (UrlKeluarDitolak $galat) {
            $pesan = PenjagaUrlKeluar::PESAN_DITOLAK_SAAT_KIRIM.': '.$galat->getMessage();
            $pengiriman->StatusHttp = null;
            $pengiriman->Respons = null;

            // Host yang belum dapat di-resolve mungkin pulih; alamat internal tidak.
            if ($galat->sementara) {
                $this->jadwalkanUlang($pengiriman, $pesan);
            } else {
                $this->tandaiGagalPermanen($pengiriman, $pesan);
            }

            return false;
        }

        $badan = (string) json_encode($pengiriman->MuatanData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $tandaTangan = $this->tandaTangan($badan, (string) $webhook->Rahasia);

        try {
            $respons = $klien->withHeaders([
                'Content-Type' => 'application/json',
                self::HEADER_TANDA_TANGAN => $tandaTangan,
                self::HEADER_PERISTIWA => $pengiriman->Peristiwa,
                self::HEADER_PENGIRIMAN => $pengiriman->Id,
            ])->timeout(10)->withBody($badan, 'application/json')->post($tujuan->url);

            /** @var int<0, max> $status */
            $status = $respons->status();
            $pengiriman->StatusHttp = $status;
            $pengiriman->Respons = $this->cuplikanRespons($respons);

            if ($respons->successful()) {
                $pengiriman->Status = StatusPengirimanPanggilanBalikWeb::Berhasil->value;
                $pengiriman->DikirimPada = now()->toImmutable();
                $pengiriman->JadwalCobaLagiPada = null;
                $pengiriman->Percobaan = $pengiriman->Percobaan + 1;
                $pengiriman->save();

                return true;
            }

            $this->jadwalkanUlang($pengiriman, "Endpoint membalas status {$respons->status()}.");

            return false;
        } catch (\Throwable $e) {
            // Pesan galat tidak menyertakan tanda tangan atau rahasia webhook.
            $this->jadwalkanUlang($pengiriman, 'Gagal menghubungi endpoint: '.class_basename($e));

            return false;
        }
    }

    /**
     * Cuplikan balasan yang aman disimpan lalu ditampilkan ke tenant.
     *
     * Hanya isi bertipe teks atau JSON, tanpa header, maksimal
     * BATAS_CUPLIKAN_RESPONS karakter, dengan karakter kendali dibuang. Tenant
     * cukup melihat pesan galat penerimanya; menyimpan balasan utuh menjadikan
     * webhook alat membaca isi layanan lain bila penjaga jaringan suatu saat
     * terlewati.
     */
    private function cuplikanRespons(Response $respons): ?string
    {
        $jenis = strtolower(trim(explode(';', $respons->header('Content-Type'))[0]));
        $bolehDisimpan = str_starts_with($jenis, 'text/')
            || $jenis === 'application/json'
            || str_ends_with($jenis, '+json');

        if (! $bolehDisimpan) {
            return $respons->body() === '' ? null : '(Isi balasan bertipe '.($jenis !== '' ? mb_substr($jenis, 0, 60) : 'tak dikenal').' tidak disimpan.)';
        }

        $teks = mb_scrub(substr($respons->body(), 0, self::BATAS_CUPLIKAN_RESPONS * 4), 'UTF-8');
        $teks = preg_replace('/[^\P{C}\n\t]+/u', '', $teks) ?? '';
        $teks = trim(mb_substr($teks, 0, self::BATAS_CUPLIKAN_RESPONS));

        return $teks === '' ? null : $teks;
    }

    public function tandaTangan(string $badan, string $rahasia): string
    {
        return 'sha256='.hash_hmac('sha256', $badan, $rahasia);
    }

    /**
     * @return array<string, mixed>
     */
    private function muatan(KotakKeluarPeristiwa $peristiwa): array
    {
        return [
            'IdPeristiwa' => $peristiwa->Id,
            'Peristiwa' => $peristiwa->NamaPeristiwa,
            'JenisAgregat' => $peristiwa->JenisAgregat,
            'AgregatId' => $peristiwa->AgregatId,
            'TerjadiPada' => $peristiwa->DibuatPada->toIso8601String(),
            'Data' => $peristiwa->MuatanData,
        ];
    }

    private function berlangganan(PanggilanBalikWeb $webhook, string $namaPeristiwa): bool
    {
        $daftar = $webhook->Peristiwa ?? [];

        foreach ($daftar as $pola) {
            if ($pola === '*' || $pola === $namaPeristiwa) {
                return true;
            }
            // Pola "Keluhan.*" berlangganan seluruh peristiwa satu domain.
            if (str_ends_with((string) $pola, '.*')
                && str_starts_with($namaPeristiwa, substr((string) $pola, 0, -1))) {
                return true;
            }
        }

        return false;
    }

    private function jadwalkanUlang(PengirimanPanggilanBalikWeb $pengiriman, string $kesalahan): void
    {
        $percobaan = $pengiriman->Percobaan + 1;
        $pengiriman->Percobaan = $percobaan;
        $pengiriman->Respons = mb_substr($pengiriman->Respons ?? $kesalahan, 0, self::BATAS_CUPLIKAN_RESPONS);

        if ($percobaan >= PengirimanPanggilanBalikWeb::BATAS_PERCOBAAN) {
            $pengiriman->Status = StatusPengirimanPanggilanBalikWeb::GagalPermanen->value;
            $pengiriman->JadwalCobaLagiPada = null;
        } else {
            $pengiriman->Status = StatusPengirimanPanggilanBalikWeb::Gagal->value;
            $pengiriman->JadwalCobaLagiPada = now()->addSeconds(60 * (2 ** ($percobaan - 1)))->toImmutable();
        }

        $pengiriman->save();
    }

    private function tandaiGagalPermanen(PengirimanPanggilanBalikWeb $pengiriman, string $kesalahan): void
    {
        $pengiriman->Status = StatusPengirimanPanggilanBalikWeb::GagalPermanen->value;
        $pengiriman->Respons = $kesalahan;
        $pengiriman->JadwalCobaLagiPada = null;
        $pengiriman->save();
    }
}
