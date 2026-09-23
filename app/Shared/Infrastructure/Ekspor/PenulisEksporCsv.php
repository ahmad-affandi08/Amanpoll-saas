<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Ekspor;

use RuntimeException;

/**
 * Penulis CSV.
 *
 * `escape: ''` bukan pilihan gaya. Dengan escape bawaan PHP (`\\`), `fputcsv`
 * tidak menggandakan tanda kutip yang didahului backslash, sehingga nilai
 * berisi `\\"` memutus selnya di tengah jalan. Excel dan LibreOffice tidak
 * mengenal escape backslash, jadi mereka membaca potongan sesudah kutip itu
 * sebagai SEL BARU -- sel yang tidak pernah melewati NetralkanRumus, karena
 * penetralan hanya memeriksa huruf pertama tiap nilai. Nama aset seperti
 * `Bor Tulang \\",=cmd|'/c calc'!A0` karena itu tetap menjadi rumus hidup
 * sekalipun penetralnya terpasang. `escape: ''` adalah perilaku RFC 4180:
 * kutip digandakan, selnya tetap satu.
 */
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
                fputcsv($berkas, NetralkanRumus::barisCsv([$kunci, $nilai]), escape: '');
            }
            if ($meta !== []) {
                fputcsv($berkas, [], escape: '');
            }

            fputcsv($berkas, NetralkanRumus::barisCsv($kepala), escape: '');
            foreach ($baris as $satu) {
                fputcsv($berkas, NetralkanRumus::barisCsv($satu), escape: '');
            }
        } finally {
            fclose($berkas);
        }
    }
}
