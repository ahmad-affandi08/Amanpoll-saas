<?php

declare(strict_types=1);

namespace App\Domain\Aset\Application\Services;

use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use App\Shared\Infrastructure\Clock\LayananZonaWaktu;
use App\Shared\Infrastructure\Persistence\BacaRelasi;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Str;

/**
 * Kartu Riwayat Alat: satu lembar berkop per aset untuk berkas akreditasi.
 *
 * Bukan ekspor tabel. Yang diperiksa surveior SNARS/JCI adalah riwayat satu
 * alat sepanjang hidupnya -- identitas, pemeliharaan, kalibrasi, perpindahan --
 * di atas satu dokumen yang dapat ditandatangani dan diarsipkan. Karena itu
 * susunannya berbagian dan diakhiri ruang tanda tangan, bukan daftar baris.
 *
 * HTML-nya dapat dibaca terpisah dari PDF-nya supaya isi kartu dapat diperiksa
 * baris demi baris tanpa membongkar berkasnya. Itu kemudahan, bukan keharusan:
 * isi PDF hasil Dompdf tetap dapat dicari sebagai teks setelah aliran isinya
 * dilepas mampatnya dan byte NUL penyela UTF-16BE-nya dibuang.
 */
final class PenyusunKartuRiwayatAset
{
    /**
     * Batas baris per bagian.
     *
     * Alat berumur belasan tahun dapat punya ratusan perintah kerja; kartu
     * setebal itu tidak lagi bisa ditandatangani sebagai satu lembar berkas.
     * Yang terbaru yang dipertahankan, dan pemotongannya dikatakan.
     */
    public const MAKS_BARIS = 100;

    /** Zona waktu cadangan bila organisasinya belum menyetelnya. */
    private const ZONA_WAKTU_BAKU = 'Asia/Jakarta';

    public function __construct(private readonly LayananZonaWaktu $layananZonaWaktu) {}

    public function pdf(Aset $aset): string
    {
        $opsi = new Options;
        $opsi->set('isRemoteEnabled', false);
        $opsi->set('isHtml5ParserEnabled', true);
        $opsi->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($opsi);
        $dompdf->loadHtml($this->html($aset), 'UTF-8');
        // Potrait, bukan landscape seperti ekspor daftar: kartu ini dibaca dan
        // ditandatangani sebagai lembar berkas, bukan tabel lebar.
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    public function html(Aset $aset): string
    {
        $aset->loadMissing(['organisasi', 'kategoriAset', 'lokasi', 'unitOrganisasi', 'modelAset.merek']);

        $organisasi = BacaRelasi::model($aset, 'organisasi');
        $zonaWaktu = BacaRelasi::teks($organisasi, 'ZonaWaktu') ?: self::ZONA_WAKTU_BAKU;
        $namaOrganisasi = BacaRelasi::teks($organisasi, 'Nama');

        $dicetakPada = $this->layananZonaWaktu
            ->keZonaWaktu($this->layananZonaWaktu->sekarangUtc(), $zonaWaktu)
            ->format('d-m-Y H:i');

        $judulDokumen = 'Kartu Riwayat Alat - '.(string) $aset->KodeAset;

        $isi = $this->kop($namaOrganisasi, $dicetakPada)
            .$this->bagianIdentitas($aset)
            .$this->bagianPemeliharaan($aset, $zonaWaktu)
            .$this->bagianKalibrasi($aset)
            .$this->bagianPerpindahan($aset, $zonaWaktu)
            .$this->bagianTandaTangan();

        return <<<HTML
        <!DOCTYPE html>
        <html lang="id"><head><meta charset="utf-8"><title>{$this->aman($judulDokumen)}</title>
        <style>
          @page { margin: 15mm; }
          body { font-family: "DejaVu Sans", sans-serif; font-size: 9pt; color: #172027; }
          .kop { border-bottom: 2px solid #17324D; padding-bottom: 3mm; margin-bottom: 5mm; text-align: center; }
          .kop .organisasi { font-size: 12pt; font-weight: bold; color: #17324D; }
          .kop h1 { font-size: 14pt; margin: 2mm 0 1mm; letter-spacing: 0.5pt; }
          .kop .cetak { font-size: 8pt; color: #6E7A82; }
          h2 { font-size: 10pt; color: #17324D; margin: 5mm 0 2mm; border-bottom: 1px solid #D7DEE3; padding-bottom: 1mm; }
          table { width: 100%; border-collapse: collapse; }
          table.identitas th { width: 32%; text-align: left; font-weight: normal; color: #6E7A82; padding: 1.2mm 2mm 1.2mm 0; vertical-align: top; }
          table.identitas td { padding: 1.2mm 0; vertical-align: top; }
          table.riwayat th { background: #F4F7F8; color: #17324D; text-align: left; padding: 1.8mm; border: 1px solid #D7DEE3; }
          table.riwayat td { padding: 1.5mm 1.8mm; border: 1px solid #E7ECEF; vertical-align: top; }
          .kosong { color: #6E7A82; font-style: italic; }
          .catatan { margin-top: 1.5mm; color: #C2413B; font-size: 8pt; }
          table.ttd { margin-top: 10mm; page-break-inside: avoid; }
          table.ttd td { width: 50%; text-align: center; vertical-align: top; padding: 0 6mm; }
          table.ttd .peran { padding-bottom: 18mm; }
          table.ttd .garis { border-top: 1px solid #172027; padding-top: 1.5mm; }
          table.ttd .isian { color: #6E7A82; font-size: 8pt; }
        </style></head>
        <body>{$isi}</body></html>
        HTML;
    }

    private function kop(string $namaOrganisasi, string $dicetakPada): string
    {
        $organisasi = $namaOrganisasi === '' ? '' : '<div class="organisasi">'.$this->aman($namaOrganisasi).'</div>';

        return '<div class="kop">'.$organisasi
            .'<h1>KARTU RIWAYAT ALAT</h1>'
            .'<div class="cetak">Dicetak pada '.$this->aman($dicetakPada).'</div></div>';
    }

    private function bagianIdentitas(Aset $aset): string
    {
        $modelAset = BacaRelasi::model($aset, 'modelAset');
        $merek = $modelAset === null ? null : BacaRelasi::model($modelAset, 'merek');

        $merekModel = array_filter([
            BacaRelasi::teks($merek, 'Nama'),
            BacaRelasi::teks($modelAset, 'Nama'),
        ], static fn (string $satu): bool => $satu !== '');

        $isian = [
            'Kode Aset' => (string) $aset->KodeAset,
            'Nama Alat' => (string) $aset->Nama,
            'Merek / Model' => implode(' / ', $merekModel),
            'Nomor Seri' => (string) ($aset->NomorSeri ?? ''),
            'Nomor Inventaris' => (string) ($aset->NomorInventaris ?? ''),
            'Kategori' => BacaRelasi::teks(BacaRelasi::model($aset, 'kategoriAset'), 'Nama'),
            'Unit' => BacaRelasi::teks(BacaRelasi::model($aset, 'unitOrganisasi'), 'Nama'),
            'Lokasi' => BacaRelasi::teks(BacaRelasi::model($aset, 'lokasi'), 'Nama'),
            'Tanggal Perolehan' => $this->tanggal($aset->TanggalPerolehan),
            'Harga Perolehan' => $this->rupiah($aset->HargaPerolehan),
            'Sumber Dana' => (string) ($aset->SumberDana ?? ''),
            'Status' => (string) $aset->Status,
            'Kondisi' => (string) $aset->Kondisi,
        ];

        $baris = '';
        foreach ($isian as $label => $nilai) {
            $baris .= '<tr><th>'.$this->aman($label).'</th><td>'.$this->aman($this->atauStrip($nilai)).'</td></tr>';
        }

        return '<h2>1. Identitas Alat</h2><table class="identitas">'.$baris.'</table>';
    }

    private function bagianPemeliharaan(Aset $aset, string $zonaWaktu): string
    {
        $total = $aset->perintahKerja()->count();

        $baris = [];
        foreach ($aset->perintahKerja()->with('penugasan.pengguna')->limit(self::MAKS_BARIS)->get() as $satu) {
            $baris[] = [
                (string) $satu->Nomor,
                (string) $satu->Jenis,
                // Tanggal nyata bila pekerjaannya sudah dimulai; selama belum,
                // yang dijadwalkan itulah satu-satunya tanggal yang ada.
                $this->waktu($satu->DimulaiPada ?? $satu->DijadwalkanMulaiPada, $zonaWaktu),
                $this->waktu($satu->DiselesaikanPada, $zonaWaktu),
                (string) ($satu->RingkasanPenyelesaian ?? ''),
                $this->teknisi($satu),
            ];
        }

        return '<h2>2. Riwayat Pemeliharaan</h2>'.$this->tabel(
            ['Nomor', 'Jenis', 'Mulai', 'Selesai', 'Ringkasan Penyelesaian', 'Teknisi'],
            $baris,
            'Belum ada perintah kerja untuk alat ini.',
            $total,
        );
    }

    private function bagianKalibrasi(Aset $aset): string
    {
        $total = $aset->pelaksanaanKalibrasi()->count();

        $baris = [];
        foreach ($aset->pelaksanaanKalibrasi()->with('penyedia')->limit(self::MAKS_BARIS)->get() as $satu) {
            $baris[] = [
                $this->tanggal($satu->TanggalKalibrasi),
                (string) ($satu->Hasil ?? ''),
                (string) ($satu->NomorSertifikat ?? ''),
                $this->tanggal($satu->TanggalBerlakuSampai),
                // Laboratorium ditulis lepas bila kalibrasinya bukan oleh penyedia terdaftar.
                (string) ($satu->Laboratorium ?? '') ?: BacaRelasi::teks(BacaRelasi::model($satu, 'penyedia'), 'Nama'),
            ];
        }

        return '<h2>3. Riwayat Kalibrasi</h2>'.$this->tabel(
            ['Tanggal Kalibrasi', 'Hasil', 'Nomor Sertifikat', 'Berlaku Sampai', 'Laboratorium / Penyedia'],
            $baris,
            'Belum ada pelaksanaan kalibrasi untuk alat ini.',
            $total,
        );
    }

    private function bagianPerpindahan(Aset $aset, string $zonaWaktu): string
    {
        $total = $aset->riwayatLokasi()->count();

        $baris = [];
        foreach ($aset->riwayatLokasi()->with(['lokasiAsal', 'lokasiTujuan'])->limit(self::MAKS_BARIS)->get() as $satu) {
            $baris[] = [
                $this->waktu($satu->DipindahkanPada, $zonaWaktu),
                BacaRelasi::teks(BacaRelasi::model($satu, 'lokasiAsal'), 'Nama'),
                BacaRelasi::teks(BacaRelasi::model($satu, 'lokasiTujuan'), 'Nama'),
                (string) ($satu->Alasan ?? ''),
            ];
        }

        return '<h2>4. Riwayat Perpindahan Lokasi</h2>'.$this->tabel(
            ['Tanggal', 'Lokasi Asal', 'Lokasi Tujuan', 'Alasan'],
            $baris,
            'Belum ada perpindahan lokasi untuk alat ini.',
            $total,
        );
    }

    private function bagianTandaTangan(): string
    {
        $kolom = '';
        foreach (['Petugas IPSRS', 'Kepala Unit'] as $peran) {
            $kolom .= '<td>'
                .'<div class="peran">'.$this->aman($peran).'</div>'
                .'<div class="garis isian">Nama: ......................................<br>'
                .'Tanggal: ......................................</div>'
                .'</td>';
        }

        return '<table class="ttd"><tr>'.$kolom.'</tr></table>';
    }

    /**
     * @param  list<string>  $kepala
     * @param  list<list<string>>  $baris
     */
    private function tabel(array $kepala, array $baris, string $pesanKosong, int $total): string
    {
        if ($baris === []) {
            return '<p class="kosong">'.$this->aman($pesanKosong).'</p>';
        }

        $kepalaHtml = '';
        foreach ($kepala as $judul) {
            $kepalaHtml .= '<th>'.$this->aman($judul).'</th>';
        }

        $isiHtml = '';
        foreach ($baris as $satu) {
            $isiHtml .= '<tr>';
            foreach ($satu as $sel) {
                $isiHtml .= '<td>'.$this->aman($this->atauStrip($sel)).'</td>';
            }
            $isiHtml .= '</tr>';
        }

        $catatan = $total > count($baris)
            ? '<p class="catatan">Menampilkan '.count($baris).' riwayat terbaru dari '.$total.' yang tercatat.</p>'
            : '';

        return '<table class="riwayat"><thead><tr>'.$kepalaHtml.'</tr></thead><tbody>'.$isiHtml.'</tbody></table>'.$catatan;
    }

    /**
     * Nama seluruh petugas yang ditugaskan pada perintah kerja.
     *
     * PeranTugas berupa teks bebas, jadi menyaring "Teknisi" saja akan
     * mengosongkan kolomnya pada organisasi yang memakai sebutan lain.
     */
    private function teknisi(PerintahKerja $perintahKerja): string
    {
        $nama = [];

        foreach ($perintahKerja->penugasan as $penugasan) {
            $satu = BacaRelasi::teks(BacaRelasi::model($penugasan, 'pengguna'), 'Nama');

            if ($satu !== '' && ! in_array($satu, $nama, true)) {
                $nama[] = $satu;
            }
        }

        return implode(', ', $nama);
    }

    /** Tanggal tanpa jam tidak dikonversi zona waktu: menggesernya mengubah harinya. */
    private function tanggal(mixed $nilai): string
    {
        return $nilai instanceof DateTimeInterface ? CarbonImmutable::instance($nilai)->format('d-m-Y') : '';
    }

    private function waktu(mixed $nilai, string $zonaWaktu): string
    {
        if (! $nilai instanceof DateTimeInterface) {
            return '';
        }

        return $this->layananZonaWaktu->keZonaWaktu($nilai, $zonaWaktu)->format('d-m-Y H:i');
    }

    private function rupiah(mixed $nilai): string
    {
        return is_numeric($nilai) ? 'Rp '.number_format((float) $nilai, 2, ',', '.') : '';
    }

    /** Sel kosong diberi strip supaya terbaca sebagai "tidak ada", bukan lupa diisi. */
    private function atauStrip(string $nilai): string
    {
        return trim($nilai) === '' ? '-' : $nilai;
    }

    private function aman(string $nilai): string
    {
        return htmlspecialchars($nilai, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Nama berkas unduhan, dipakai controller pada header Content-Disposition.
     *
     * `KodeAset` diketik pengguna dan hanya dibatasi panjangnya, jadi ia dapat
     * memuat tanda kutip, garis miring, maupun persen. Ketiganya berbahaya di
     * tempat nama berkas dipakai: kutip memalsukan parameter `filename` pada
     * header, dan garis miring membuat `HeaderUtils::makeDisposition()`
     * melempar sehingga unduhannya gagal sama sekali. `Str::slug` menyisakan
     * huruf, angka, dan tanda hubung -- cukup untuk tetap terbaca tanpa
     * menyerahkan bentuk nama berkasnya kepada penyusun data.
     *
     * Pemisahnya diubah menjadi tanda hubung lebih dulu karena `Str::slug`
     * MEMBUANG tanda baca alih-alih menggantinya: tanpa langkah ini
     * `AST/2026/001` -- bentuk penomoran yang lazim -- menjadi `ast2026001`
     * yang tidak lagi terbaca sebagai nomor aset oleh siapa pun.
     */
    public function namaBerkas(Aset $aset): string
    {
        $kode = Str::slug((string) preg_replace('/[^\p{L}\p{N}]+/u', '-', (string) $aset->KodeAset));

        return 'kartu-riwayat-'.($kode === '' ? 'aset' : $kode).'.pdf';
    }
}
