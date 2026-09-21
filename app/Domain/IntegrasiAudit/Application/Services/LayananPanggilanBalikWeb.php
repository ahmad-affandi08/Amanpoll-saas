<?php

declare(strict_types=1);

namespace App\Domain\IntegrasiAudit\Application\Services;

use App\Domain\IntegrasiAudit\Domain\Enums\StatusPengirimanPanggilanBalikWeb;
use App\Domain\IntegrasiAudit\Infrastructure\Persistence\Models\KotakKeluarPeristiwa;
use App\Domain\IntegrasiAudit\Infrastructure\Persistence\Models\PanggilanBalikWeb;
use App\Domain\IntegrasiAudit\Infrastructure\Persistence\Models\PengirimanPanggilanBalikWeb;
use App\Domain\IntegrasiAudit\Jobs\KirimPanggilanBalikWeb;
use Illuminate\Support\Facades\Http;

/**
 * Menerbitkan peristiwa kotak keluar ke endpoint webhook yang berlangganan,
 * lalu mengirimkannya dengan tanda tangan dan percobaan ulang (19.04, 19.05).
 */
final class LayananPanggilanBalikWeb
{
    public const HEADER_TANDA_TANGAN = 'X-Amanpoll-Signature';

    public const HEADER_PERISTIWA = 'X-Amanpoll-Event';

    public const HEADER_PENGIRIMAN = 'X-Amanpoll-Delivery';

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

    /**
     * Mengirim satu pengiriman ke endpoint tujuan dan mencatat hasilnya.
     * Kegagalan dijadwalkan ulang dengan jeda menaik sampai batas percobaan.
     */
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

        $badan = (string) json_encode($pengiriman->MuatanData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $tandaTangan = $this->tandaTangan($badan, (string) $webhook->Rahasia);

        try {
            $respons = Http::withHeaders([
                'Content-Type' => 'application/json',
                self::HEADER_TANDA_TANGAN => $tandaTangan,
                self::HEADER_PERISTIWA => $pengiriman->Peristiwa,
                self::HEADER_PENGIRIMAN => $pengiriman->Id,
            ])->timeout(10)->withBody($badan, 'application/json')->post($webhook->Url);

            /** @var int<0, max> $status */
            $status = $respons->status();
            $pengiriman->StatusHttp = $status;
            $pengiriman->Respons = mb_substr($respons->body(), 0, 2000);

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
        $pengiriman->Respons = mb_substr($pengiriman->Respons ?? $kesalahan, 0, 2000);

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
