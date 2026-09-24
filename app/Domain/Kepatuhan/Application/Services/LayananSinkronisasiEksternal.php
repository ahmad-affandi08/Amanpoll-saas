<?php

declare(strict_types=1);

namespace App\Domain\Kepatuhan\Application\Services;

use App\Domain\Kepatuhan\Domain\Enums\ArahSinkronisasiEksternal;
use App\Domain\Kepatuhan\Domain\Enums\StatusIntegrasiEksternal;
use App\Domain\Kepatuhan\Domain\Enums\StatusSinkronisasiEksternal;
use App\Domain\Kepatuhan\Infrastructure\Persistence\Models\IntegrasiEksternal;
use App\Domain\Kepatuhan\Infrastructure\Persistence\Models\SinkronisasiEksternal;
use App\Domain\Kepatuhan\Infrastructure\Services\PenyusunHeaderIntegrasi;
use App\Domain\Kepatuhan\Jobs\JalankanSinkronisasiEksternal;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use App\Shared\Infrastructure\Keamanan\PenjagaUrlKeluar;
use App\Shared\Infrastructure\Keamanan\UrlKeluarDitolak;
use Throwable;

/** Abstraksi tarik/dorong data ke sistem eksternal (19.03). */
final class LayananSinkronisasiEksternal
{
    public function __construct(
        private readonly RegistriAdapterSinkronisasi $registri,
        private readonly PenyusunHeaderIntegrasi $header,
        private readonly PenjagaUrlKeluar $penjaga,
    ) {}

    /** Menyiapkan satu baris sinkronisasi lalu menyerahkannya ke antrean. */
    public function antrikan(IntegrasiEksternal $integrasi, string $jenisProses, string $arah): SinkronisasiEksternal
    {
        $sinkronisasi = $this->mulai($integrasi, $jenisProses, $arah);
        JalankanSinkronisasiEksternal::dispatch($sinkronisasi->Id);

        return $sinkronisasi;
    }

    /** Menjalankan satu baris sinkronisasi lewat adapter jenis integrasinya. */
    public function jalankan(SinkronisasiEksternal $sinkronisasi): SinkronisasiEksternal
    {
        $integrasi = IntegrasiEksternal::query()
            ->withoutGlobalScopes()
            ->whereKey($sinkronisasi->IntegrasiEksternalId)
            ->firstOrFail();

        $adapter = $this->registri->untuk($integrasi);
        $hasil = $sinkronisasi->Arah === ArahSinkronisasiEksternal::Tarik->value
            ? $adapter->tarik($integrasi, $sinkronisasi->JenisProses)
            : $adapter->dorong($integrasi, $sinkronisasi->JenisProses);

        $integrasi->TerakhirSinkronPada = now()->toImmutable();
        $integrasi->save();

        return $this->selesaikan($sinkronisasi, $hasil['berhasil'], $hasil['gagal']);
    }

    public function gagalkan(SinkronisasiEksternal $sinkronisasi, string $pesanKesalahan): SinkronisasiEksternal
    {
        return $this->selesaikan($sinkronisasi, 0, max(1, $sinkronisasi->JumlahGagal), $pesanKesalahan);
    }

    public function mulai(IntegrasiEksternal $integrasi, string $jenisProses, string $arah): SinkronisasiEksternal
    {
        if ($integrasi->Status !== StatusIntegrasiEksternal::Aktif->value) {
            throw new AturanBisnisDilanggar('Sinkronisasi hanya dapat dijalankan pada integrasi aktif.');
        }
        if (! in_array($arah, [ArahSinkronisasiEksternal::Tarik->value, ArahSinkronisasiEksternal::Dorong->value], true)) {
            throw new AturanBisnisDilanggar('Arah sinkronisasi tidak dikenal.');
        }

        return SinkronisasiEksternal::create([
            'OrganisasiId' => $integrasi->OrganisasiId,
            'IntegrasiEksternalId' => $integrasi->Id,
            'JenisProses' => $jenisProses,
            'Arah' => $arah,
            'Status' => StatusSinkronisasiEksternal::Diproses->value,
            'MulaiPada' => now(),
        ]);
    }

    /**
     * @param  int<0, max>  $berhasil
     * @param  int<0, max>  $gagal
     */
    public function selesaikan(SinkronisasiEksternal $sinkronisasi, int $berhasil, int $gagal, ?string $pesanKesalahan = null): SinkronisasiEksternal
    {
        $sinkronisasi->JumlahData = $berhasil + $gagal;
        $sinkronisasi->JumlahBerhasil = $berhasil;
        $sinkronisasi->JumlahGagal = $gagal;
        $sinkronisasi->SelesaiPada = now()->toImmutable();
        $sinkronisasi->PesanKesalahan = $pesanKesalahan === null ? null : $this->pesanAman($pesanKesalahan);
        $sinkronisasi->Status = match (true) {
            $gagal === 0 && $pesanKesalahan === null => StatusSinkronisasiEksternal::Berhasil->value,
            $berhasil === 0 => StatusSinkronisasiEksternal::Gagal->value,
            default => StatusSinkronisasiEksternal::Sebagian->value,
        };
        $sinkronisasi->save();

        return $sinkronisasi->refresh();
    }

    /**
     * Uji koneksi ke sistem tujuan. Kegagalan menandai integrasi bermasalah
     * supaya tidak dipakai sinkronisasi berikutnya tanpa diperbaiki.
     *
     * @return array{berhasil: bool, status: int|null, pesan: string}
     */
    public function ujiKoneksi(IntegrasiEksternal $integrasi): array
    {
        if (empty($integrasi->UrlDasar)) {
            return ['berhasil' => false, 'status' => null, 'pesan' => 'URL dasar integrasi belum diisi.'];
        }

        try {
            $tujuan = $this->penjaga->periksa((string) $integrasi->UrlDasar);
            $respons = $this->penjaga->klien($tujuan)
                ->timeout(10)
                ->withHeaders($this->header->untuk($integrasi))
                ->get($tujuan->url);

            $berhasil = $respons->successful();
            $integrasi->Status = $berhasil ? StatusIntegrasiEksternal::Aktif->value : StatusIntegrasiEksternal::Bermasalah->value;
            $integrasi->save();

            return [
                'berhasil' => $berhasil,
                'status' => $respons->status(),
                'pesan' => $berhasil ? 'Koneksi berhasil.' : "Sistem tujuan membalas status {$respons->status()}.",
            ];
        } catch (UrlKeluarDitolak $e) {
            // Ditolak sebelum ada permintaan keluar; kredensial tidak pernah terkirim.
            $integrasi->Status = StatusIntegrasiEksternal::Bermasalah->value;
            $integrasi->save();

            return ['berhasil' => false, 'status' => null, 'pesan' => PenjagaUrlKeluar::PESAN_DITOLAK_SAAT_KIRIM.': '.$e->getMessage()];
        } catch (Throwable $e) {
            $integrasi->Status = StatusIntegrasiEksternal::Bermasalah->value;
            $integrasi->save();

            return ['berhasil' => false, 'status' => null, 'pesan' => $this->pesanAman($e->getMessage())];
        }
    }

    /** Pesan kegagalan yang disimpan tidak boleh membocorkan kredensial yang ikut terbawa pada URL atau header. */
    private function pesanAman(string $pesan): string
    {
        $bersih = preg_replace('/(Bearer\s+|api[_-]?key=|token=|password=)[^\s&"\']+/i', '$1[disamarkan]', $pesan) ?? $pesan;
        $bersih = preg_replace('#(https?://)[^\s]*@#i', '$1[disamarkan]@', $bersih) ?? $bersih;

        return mb_substr($bersih, 0, 1000);
    }
}
