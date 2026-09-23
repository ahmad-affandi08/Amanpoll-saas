<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Ekspor;

use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;

/**
 * Penulis XLSX lewat OpenSpout, yang menulis secara streaming.
 *
 * Nilainya dinetralkan meski XLSX punya tipe sel: OpenSpout mendeteksi awalan
 * `=` dan menuliskannya sebagai sel rumus `<f>` yang sungguhan, sehingga nama
 * aset bikinan tenant dapat berubah menjadi rumus yang hidup saat dibuka.
 */
final class PenulisEksporXlsx implements PenulisEkspor
{
    public function format(): FormatEkspor
    {
        return FormatEkspor::Xlsx;
    }

    public function tulis(string $pathLokal, array $kepala, iterable $baris, array $meta): void
    {
        $penulis = new Writer;
        $penulis->openToFile($pathLokal);

        try {
            $gayaTebal = (new Style)->withFontBold(true);

            foreach ($meta as $kunci => $nilai) {
                $penulis->addRow(Row::fromValues(NetralkanRumus::barisXlsx([$kunci, $nilai])));
            }
            if ($meta !== []) {
                $penulis->addRow(Row::fromValues([]));
            }

            $penulis->addRow(Row::fromValuesWithStyle(NetralkanRumus::barisXlsx($kepala), $gayaTebal));
            foreach ($baris as $satu) {
                $penulis->addRow(Row::fromValues(NetralkanRumus::barisXlsx($satu)));
            }
        } finally {
            $penulis->close();
        }
    }
}
