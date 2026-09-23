<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Ekspor;

use RuntimeException;

/** Penulis CSV. */
final class PenulisEksporCsv implements PenulisEkspor
{
    public function format(): FormatEkspor
    {
        return FormatEkspor::Csv;
    }

    public function tulis(string $pathLokal, array $kepala, iterable $baris, array $meta): void
    {
        $berkas = fopen($pathLokal, 'wb');
        if ($berkas === false) {
            throw new RuntimeException("Tidak dapat membuka {$pathLokal} untuk ditulis.");
        }

        try {
            // BOM UTF-8 supaya Excel di Windows tidak salah membaca huruf beraksen.
            fwrite($berkas, "\xEF\xBB\xBF");

            foreach ($meta as $kunci => $nilai) {
                fputcsv($berkas, NetralkanRumus::barisCsv([$kunci, $nilai]), escape: '\\');
            }
            if ($meta !== []) {
                fputcsv($berkas, [], escape: '\\');
            }

            fputcsv($berkas, NetralkanRumus::barisCsv($kepala), escape: '\\');
            foreach ($baris as $satu) {
                fputcsv($berkas, NetralkanRumus::barisCsv($satu), escape: '\\');
            }
        } finally {
            fclose($berkas);
        }
    }
}
