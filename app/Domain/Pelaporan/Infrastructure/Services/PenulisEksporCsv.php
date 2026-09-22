<?php

declare(strict_types=1);

namespace App\Domain\Pelaporan\Infrastructure\Services;

use App\Domain\Pelaporan\Domain\Contracts\PenulisEkspor;
use App\Domain\Pelaporan\Domain\Enums\FormatEkspor;
use RuntimeException;

/** Penulis CSV. */
final class PenulisEksporCsv implements PenulisEkspor
{
    private const AWALAN_RUMUS = "=+-@\t\r";

    public function format(): FormatEkspor
    {
        return FormatEkspor::Csv;
    }

    public function tulis(string $pathLokal, array $kepala, array $baris, array $meta): void
    {
        $berkas = fopen($pathLokal, 'wb');
        if ($berkas === false) {
            throw new RuntimeException("Tidak dapat membuka {$pathLokal} untuk ditulis.");
        }

        try {
            // BOM UTF-8 supaya Excel di Windows tidak salah membaca huruf beraksen.
            fwrite($berkas, "\xEF\xBB\xBF");

            foreach ($meta as $kunci => $nilai) {
                fputcsv($berkas, $this->netralkan([$kunci, $nilai]), escape: '\\');
            }
            if ($meta !== []) {
                fputcsv($berkas, [], escape: '\\');
            }

            fputcsv($berkas, $this->netralkan($kepala), escape: '\\');
            foreach ($baris as $satu) {
                fputcsv($berkas, $this->netralkan($satu), escape: '\\');
            }
        } finally {
            fclose($berkas);
        }
    }

    /**
     * Menetralkan injeksi rumus (24). Isi baris berasal dari data tenant — nama
     * aset, gudang, penyedia — dan Excel memperlakukan sel yang diawali `=`,
     * `+`, `-`, `@`, tab, atau carriage return sebagai rumus, bukan teks. Satu
     * pengguna yang menamai asetnya `=cmd|...` karena itu dapat mengeksekusi
     * perintah di komputer rekan kerjanya yang membuka hasil ekspor.
     *
     * Kutip tunggal di depan adalah penanda "ini teks" yang dikenali Excel dan
     * LibreOffice, dan tidak ikut tampil di selnya.
     *
     * @param  array<int|string, mixed>  $baris
     * @return array<int|string, mixed>
     */
    private function netralkan(array $baris): array
    {
        return array_map(
            function (mixed $nilai): mixed {
                if (! is_string($nilai) || $nilai === '') {
                    return $nilai;
                }

                return str_contains(self::AWALAN_RUMUS, $nilai[0]) ? "'".$nilai : $nilai;
            },
            $baris,
        );
    }
}
