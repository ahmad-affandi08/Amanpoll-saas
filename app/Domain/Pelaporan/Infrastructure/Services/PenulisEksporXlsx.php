<?php

declare(strict_types=1);

namespace App\Domain\Pelaporan\Infrastructure\Services;

use App\Domain\Pelaporan\Domain\Contracts\PenulisEkspor;
use App\Domain\Pelaporan\Domain\Enums\FormatEkspor;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;

/** Penulis XLSX lewat OpenSpout, yang menulis secara streaming. */
final class PenulisEksporXlsx implements PenulisEkspor
{
    public function format(): FormatEkspor
    {
        return FormatEkspor::Xlsx;
    }

    public function tulis(string $pathLokal, array $kepala, array $baris, array $meta): void
    {
        $penulis = new Writer;
        $penulis->openToFile($pathLokal);

        try {
            $gayaTebal = (new Style)->withFontBold(true);

            foreach ($meta as $kunci => $nilai) {
                $penulis->addRow(Row::fromValues([$kunci, $nilai]));
            }
            if ($meta !== []) {
                $penulis->addRow(Row::fromValues([]));
            }

            $penulis->addRow(Row::fromValuesWithStyle($kepala, $gayaTebal));
            foreach ($baris as $satu) {
                $penulis->addRow(Row::fromValues($satu));
            }
        } finally {
            $penulis->close();
        }
    }
}
