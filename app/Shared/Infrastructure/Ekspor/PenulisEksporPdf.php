<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Ekspor;

use Dompdf\Dompdf;
use Dompdf\Options;
use RuntimeException;

/** Penulis PDF lewat Dompdf. */
final class PenulisEksporPdf implements PenulisEkspor
{
    /** Batas baris yang dicetak ke PDF. */
    public const BATAS_BARIS = 2000;

    /** Kunci meta yang sudah tampil di kop, jadi tidak diulang di tabel keterangan. */
    private const META_DI_KOP = ['Judul', 'Organisasi'];

    /**
     * @param  string|null  $logoDataUri  logo kop sebagai data URI; lihat LogoKopEkspor
     */
    public function __construct(private readonly ?string $logoDataUri = null) {}

    public function format(): FormatEkspor
    {
        return FormatEkspor::Pdf;
    }

    public function tulis(string $pathLokal, array $kepala, iterable $baris, array $meta): void
    {
        // Pembacaan berhenti pada BATAS_BARIS + 1: baris kelebihan yang pertama
        // sudah cukup membuktikan daftarnya terpotong, dan sisanya tidak pernah
        // diminta. $baris lazimnya generator yang menarik potongan dari basis
        // data; menghabiskannya hanya demi angka total membuat ekspor 50.000
        // aset menghidrasi seluruh model beserta relasinya lalu membuangnya.
        $barisCetak = [];
        $dipotong = false;

        foreach ($baris as $satu) {
            if (count($barisCetak) === self::BATAS_BARIS) {
                $dipotong = true;

                break;
            }

            $barisCetak[] = $satu;
        }

        $opsi = new Options;
        // Tetap mati. Menghidupkannya membuat Dompdf mengambil sendiri URL yang
        // tertulis di dokumen -- termasuk LogoUrl yang diisi tenant -- sehingga
        // setiap ekspor PDF menjadi SSRF atas nama server. Logo kop karena itu
        // disisipkan sebagai data URI yang byte-nya sudah dibaca dan diperiksa
        // aplikasi sendiri (LogoKopEkspor), bukan diambil Dompdf.
        $opsi->set('isRemoteEnabled', false);
        $opsi->set('isHtml5ParserEnabled', true);
        $opsi->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($opsi);
        $dompdf->loadHtml($this->html($kepala, $barisCetak, $meta, $dipotong), 'UTF-8');
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
    private function html(array $kepala, array $baris, array $meta, bool $dipotong): string
    {
        $judul = $meta['Judul'] ?? 'Laporan Amanpoll';
        // Judul dapat berasal dari pemanggil, jadi ia juga dilolosi sebelum
        // masuk <title>; hanya isi tabel yang selama ini dilolosi.
        $judulAman = e($judul);
        $kop = $this->kop($judul, $meta['Organisasi'] ?? '');

        $barisMeta = '';
        foreach ($meta as $kunci => $nilai) {
            if (in_array($kunci, self::META_DI_KOP, true)) {
                continue;
            }

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

        // Tanpa angka total: totalnya memang tidak lagi dihitung, dan menebaknya
        // hanya akan mencetak angka yang tidak pernah diukur.
        $catatan = $dipotong
            ? '<p class="catatan">Hanya '.number_format(self::BATAS_BARIS, 0, ',', '.')
                .' baris pertama yang dicetak. Unduh format CSV atau XLSX untuk daftar selengkapnya.</p>'
            : '';

        return <<<HTML
<!DOCTYPE html>
<html lang="id"><head><meta charset="utf-8"><title>{$judulAman}</title>
<style>
  @page { margin: 14mm; }
  body { font-family: "DejaVu Sans", sans-serif; font-size: 9pt; color: #172027; }
  h1 { font-size: 15pt; color: #17324D; margin: 0 0 1mm; }
  table.kop { width: 100%; border-collapse: collapse; border-bottom: 2px solid #17324D; margin-bottom: 4mm; }
  table.kop td { vertical-align: middle; padding: 0 0 3mm; }
  table.kop td.logo { width: 22mm; padding-right: 4mm; }
  table.kop img { max-width: 20mm; max-height: 20mm; }
  table.kop .organisasi { font-size: 11pt; font-weight: bold; color: #17324D; }
  table.meta { margin-bottom: 5mm; border-collapse: collapse; }
  table.meta th { text-align: left; padding: 0 6mm 1mm 0; color: #6E7A82; font-weight: normal; }
  table.data { width: 100%; border-collapse: collapse; }
  table.data th { background: #F4F7F8; color: #17324D; text-align: left; padding: 2mm; border-bottom: 1px solid #D7DEE3; }
  table.data td { padding: 1.6mm 2mm; border-bottom: 1px solid #E7ECEF; }
  table.data td.angka { text-align: right; }
  .catatan { margin-top: 4mm; color: #C2413B; font-size: 8pt; }
</style></head>
<body>
  {$kop}
  <table class="meta">{$barisMeta}</table>
  <table class="data"><thead><tr>{$kepalaHtml}</tr></thead><tbody>{$isiHtml}</tbody></table>
  {$catatan}
</body></html>
HTML;
    }

    /**
     * Kepala dokumen: nama organisasi dan judul laporan, dengan logo bila ada.
     *
     * Logonya dilewati diam-diam bila tidak tersedia. Berkas ekspor yang sampai
     * tanpa logo tetap dapat dibaca dan ditelusuri; ekspor yang gagal karena
     * logonya tidak terbaca tidak berguna bagi siapa pun.
     *
     * Publik supaya penjaganya dapat memeriksa kop yang sungguh dirender: teks
     * PDF masih dapat ditelusuri dari alirannya, tetapi keputusan memasang atau
     * menolak sebuah gambar tidak meninggalkan jejak apa pun di byte hasilnya.
     */
    public function kop(string $judul, string $organisasi): string
    {
        // Hanya data URI yang dipasang. Lapis kedua di bawah isRemoteEnabled:
        // bila suatu saat ada yang mengoper URL mentah ke sini, gambarnya tetap
        // tidak ikut -- dan dokumennya tidak pernah menyimpan URL yang bisa
        // diambil siapa pun yang membukanya nanti.
        $logo = $this->logoDataUri !== null && str_starts_with($this->logoDataUri, 'data:')
            ? '<td class="logo"><img src="'.e($this->logoDataUri).'" alt=""></td>'
            : '';

        $namaOrganisasi = $organisasi === ''
            ? ''
            : '<div class="organisasi">'.e($organisasi).'</div>';

        return '<table class="kop"><tr>'.$logo
            .'<td>'.$namaOrganisasi.'<h1>'.e($judul).'</h1></td>'
            .'</tr></table>';
    }
}
