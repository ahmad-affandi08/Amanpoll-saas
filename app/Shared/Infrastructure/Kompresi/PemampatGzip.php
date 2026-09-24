<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Kompresi;

use RuntimeException;

/**
 * Gzip streaming: berkas dibaca per potongan, tidak pernah dimuat utuh ke memori.
 * Header gzip dari zlib tidak memuat nama maupun waktu, jadi isi yang sama selalu
 * menghasilkan byte yang sama (syarat berbagi salinan fisik).
 */
final class PemampatGzip
{
    private const POTONGAN_BYTE = 1_048_576;

    /**
     * @param  float  $hematMinimalPersen  0 berarti cukup lebih kecil dari aslinya.
     * @return ?string Lokasi berkas gzip sementara, atau null bila tidak cukup hemat.
     */
    public function pampatkan(string $lokasiSumber, int $ukuranAsli, float $hematMinimalPersen): ?string
    {
        $level = max(1, min(9, (int) config('amanpoll.kompresi.gzip.level', 9)));
        $tujuan = BerkasSementara::buat();

        try {
            $this->tulis($lokasiSumber, $tujuan, $level);
            $ukuran = BerkasSementara::ukuran($tujuan);
        } catch (\Throwable $galat) {
            BerkasSementara::hapus($tujuan);
            throw $galat;
        }

        $batas = $ukuranAsli * (1 - $hematMinimalPersen / 100);
        if ($ukuran >= $ukuranAsli || $ukuran > $batas) {
            BerkasSementara::hapus($tujuan);

            return null;
        }

        return $tujuan;
    }

    private function tulis(string $lokasiSumber, string $tujuan, int $level): void
    {
        $masuk = fopen($lokasiSumber, 'rb');
        if ($masuk === false) {
            throw new RuntimeException('Berkas sumber tidak dapat dibaca.');
        }

        $keluar = gzopen($tujuan, 'wb'.$level);
        if ($keluar === false) {
            fclose($masuk);
            throw new RuntimeException('Berkas gzip sementara tidak dapat dibuat.');
        }

        try {
            while (! feof($masuk)) {
                $potongan = fread($masuk, self::POTONGAN_BYTE);
                if ($potongan === false) {
                    throw new RuntimeException('Gagal membaca berkas sumber.');
                }
                if ($potongan !== '' && gzwrite($keluar, $potongan) === false) {
                    throw new RuntimeException('Gagal menulis gzip.');
                }
            }
        } finally {
            fclose($masuk);
            gzclose($keluar);
        }
    }
}
