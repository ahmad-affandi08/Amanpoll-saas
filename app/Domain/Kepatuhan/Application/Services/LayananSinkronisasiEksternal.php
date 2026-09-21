<?php

declare(strict_types=1);

namespace App\Domain\Kepatuhan\Application\Services;

use App\Domain\Kepatuhan\Infrastructure\Persistence\Models\IntegrasiEksternal;
use App\Domain\Kepatuhan\Infrastructure\Persistence\Models\SinkronisasiEksternal;
use App\Domain\Kepatuhan\Infrastructure\Services\PenyusunHeaderIntegrasi;
use App\Domain\Kepatuhan\Jobs\JalankanSinkronisasiEksternal;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Abstraksi tarik/dorong data ke sistem eksternal (19.03). Setiap jalannya
 * dicatat sebagai satu baris SinkronisasiEksternal sehingga status, jumlah
 * data, dan kegagalannya dapat ditinjau tanpa membuka log server.
 */
final class LayananSinkronisasiEksternal
{
    public function __construct(
        private readonly RegistriAdapterSinkronisasi $registri,
        private readonly PenyusunHeaderIntegrasi $header,
    ) {}

    /**
     * Menyiapkan satu baris sinkronisasi lalu menyerahkannya ke antrean,
     * sehingga permintaan pengguna tidak menunggu sistem eksternal.
     */
    public function antrikan(IntegrasiEksternal $integrasi, string $jenisProses, string $arah): SinkronisasiEksternal
    {
        $sinkronisasi = $this->mulai($integrasi, $jenisProses, $arah);
        JalankanSinkronisasiEksternal::dispatch($sinkronisasi->Id);

        return $sinkronisasi;
    }

    /**
     * Menjalankan satu baris sinkronisasi lewat adapter jenis integrasinya.
     * Kegagalan dilempar kembali supaya job mencobanya ulang.
     */
    public function jalankan(SinkronisasiEksternal $sinkronisasi): SinkronisasiEksternal
    {
        $integrasi = IntegrasiEksternal::query()
            ->withoutGlobalScopes()
            ->whereKey($sinkronisasi->IntegrasiEksternalId)
            ->firstOrFail();

        $adapter = $this->registri->untuk($integrasi);
        $hasil = $sinkronisasi->Arah === SinkronisasiEksternal::ARAH_TARIK
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
        if ($integrasi->Status !== IntegrasiEksternal::STATUS_AKTIF) {
            throw new AturanBisnisDilanggar('Sinkronisasi hanya dapat dijalankan pada integrasi aktif.');
        }
        if (! in_array($arah, [SinkronisasiEksternal::ARAH_TARIK, SinkronisasiEksternal::ARAH_DORONG], true)) {
            throw new AturanBisnisDilanggar('Arah sinkronisasi tidak dikenal.');
        }

        return SinkronisasiEksternal::create([
            'OrganisasiId' => $integrasi->OrganisasiId,
            'IntegrasiEksternalId' => $integrasi->Id,
            'JenisProses' => $jenisProses,
            'Arah' => $arah,
            'Status' => SinkronisasiEksternal::STATUS_DIPROSES,
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
            $gagal === 0 && $pesanKesalahan === null => SinkronisasiEksternal::STATUS_BERHASIL,
            $berhasil === 0 => SinkronisasiEksternal::STATUS_GAGAL,
            default => SinkronisasiEksternal::STATUS_SEBAGIAN,
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
            $respons = Http::timeout(10)
                ->withHeaders($this->header->untuk($integrasi))
                ->get((string) $integrasi->UrlDasar);

            $berhasil = $respons->successful();
            $integrasi->Status = $berhasil ? IntegrasiEksternal::STATUS_AKTIF : IntegrasiEksternal::STATUS_BERMASALAH;
            $integrasi->save();

            return [
                'berhasil' => $berhasil,
                'status' => $respons->status(),
                'pesan' => $berhasil ? 'Koneksi berhasil.' : "Sistem tujuan membalas status {$respons->status()}.",
            ];
        } catch (Throwable $e) {
            $integrasi->Status = IntegrasiEksternal::STATUS_BERMASALAH;
            $integrasi->save();

            return ['berhasil' => false, 'status' => null, 'pesan' => $this->pesanAman($e->getMessage())];
        }
    }

    /**
     * Pesan kegagalan yang disimpan tidak boleh membocorkan kredensial yang
     * ikut terbawa pada URL atau header.
     */
    private function pesanAman(string $pesan): string
    {
        $bersih = preg_replace('/(Bearer\s+|api[_-]?key=|token=|password=)[^\s&"\']+/i', '$1[disamarkan]', $pesan) ?? $pesan;
        $bersih = preg_replace('#(https?://)[^\s]*@#i', '$1[disamarkan]@', $bersih) ?? $bersih;

        return mb_substr($bersih, 0, 1000);
    }
}
