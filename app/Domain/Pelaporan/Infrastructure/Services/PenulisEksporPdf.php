<?php

declare(strict_types=1);

namespace App\Domain\Pelaporan\Infrastructure\Services;

use App\Domain\Pelaporan\Domain\Contracts\PenulisEkspor;
use App\Domain\Pelaporan\Domain\Enums\FormatEkspor;
use Dompdf\Dompdf;
use Dompdf\Options;
use RuntimeException;

/** Penulis PDF lewat Dompdf. */
final class PenulisEksporPdf implements PenulisEkspor
{
    /** Batas baris yang dicetak ke PDF. */
    public const BATAS_BARIS = 2000;

    public function format(): FormatEkspor
    {
        return FormatEkspor::Pdf;
    }

    public function tulis(string $pathLokal, array $kepala, array $baris, array $meta): void
    {
        $dipotong = count($baris) > self::BATAS_BARIS;
        $barisCetak = $dipotong ? array_slice($baris, 0, self::BATAS_BARIS) : $baris;

        $opsi = new Options;
        $opsi->set('isRemoteEnabled', false);
        $opsi->set('isHtml5ParserEnabled', true);
        $opsi->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($opsi);
        $dompdf->loadHtml($this->html($kepala, $barisCetak, $meta, $dipotong, count($baris)), 'UTF-8');
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        $keluaran = $dompdf->output();
        if (file_put_contents($pathLokal, $keluaran) === false) {
            throw new RuntimeException("Gagal menulis PDF ke {$pathLokal}.");
        }
    }

    /**
     * @param  list<string>  $kepala
     * @param  list<list<string|float>>  $baris
     * @param  array<string, string>  $meta
     */
    private function html(array $kepala, array $baris, array $meta, bool $dipotong, int $totalBaris): string
    {
        $judul = $meta['Judul'] ?? 'Laporan Amanpoll';

        $barisMeta = '';
        foreach ($meta as $kunci => $nilai) {
            $barisMeta .= '<tr><th>'.e($kunci).'</th><td>'.e($nilai).'</td></tr>';
        }

        $kepalaHtml = '';
        foreach ($kepala as $kolom) {
            $kepalaHtml .= '<th>'.e($kolom).'</th>';
        }

        $isiHtml = '';
        foreach ($baris as $satu) {
            $isiHtml .= '<tr>';
            foreach ($satu as $sel) {
                $kelas = is_float($sel) ? ' class="angka"' : '';
                $isiHtml .= '<td'.$kelas.'>'.e(is_float($sel) ? rtrim(rtrim(number_format($sel, 2, ',', '.'), '0'), ',') : (string) $sel).'</td>';
            }
            $isiHtml .= '</tr>';
        }

        $catatan = $dipotong
            ? '<p class="catatan">Dokumen ini memuat '.count($baris).' dari '.$totalBaris
                .' baris. Unduh format CSV atau XLSX untuk data lengkap.</p>'
            : '';

        return <<<HTML
<!DOCTYPE html>
<html lang="id"><head><meta charset="utf-8"><title>{$judul}</title>
<style>
  @page { margin: 14mm; }
  body { font-family: "DejaVu Sans", sans-serif; font-size: 9pt; color: #172027; }
  h1 { font-size: 15pt; color: #17324D; margin: 0 0 4mm; }
  table.meta { margin-bottom: 5mm; border-collapse: collapse; }
  table.meta th { text-align: left; padding: 0 6mm 1mm 0; color: #6E7A82; font-weight: normal; }
  table.data { width: 100%; border-collapse: collapse; }
  table.data th { background: #F4F7F8; color: #17324D; text-align: left; padding: 2mm; border-bottom: 1px solid #D7DEE3; }
  table.data td { padding: 1.6mm 2mm; border-bottom: 1px solid #E7ECEF; }
  table.data td.angka { text-align: right; }
  .catatan { margin-top: 4mm; color: #C2413B; font-size: 8pt; }
</style></head>
<body>
  <h1>{$judul}</h1>
  <table class="meta">{$barisMeta}</table>
  <table class="data"><thead><tr>{$kepalaHtml}</tr></thead><tbody>{$isiHtml}</tbody></table>
  {$catatan}
</body></html>
HTML;
    }
}
