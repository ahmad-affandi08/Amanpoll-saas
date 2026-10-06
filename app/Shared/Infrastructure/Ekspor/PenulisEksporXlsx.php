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
    /**
     * @param  bool  $denganMerek  footer "Powered by Amanpoll" di baris terakhir
     *
     * Baku mati, sama seperti PenulisEksporCsv: penulis format ini cocok dipakai
     * lagi untuk templat yang diunggah kembali apa adanya, dan footer di situ
     * akan terbaca sebagai baris data ekstra oleh pengurainya. Hanya laporan
     * yang dibaca manusia (EksporDaftar, LayananEksporLaporan) yang menyalakannya.
     */
    public function __construct(private readonly bool $denganMerek = false) {}

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

            if ($this->denganMerek) {
                $gayaMerek = (new Style)->withFontItalic(true)->withFontColor('6E7A82')->withFontSize(9);

                // OpenSpout tidak punya API penyisipan gambar (lihat PenulisEksporPdf
                // untuk lambang bergambar); baris penutup ini teks biasa, bukan rumus --
                // tidak diawali karakter yang dibaca NetralkanRumus sebagai pemicu.
                $penulis->addRow(Row::fromValues([]));
                $penulis->addRow(Row::fromValuesWithStyle(['Powered by Amanpoll'], $gayaMerek));
            }
        } finally {
            $penulis->close();
        }
    }
}
