<?php

declare(strict_types=1);

namespace Tests\Feature\Keamanan;

use App\Shared\Infrastructure\Ekspor\PenulisEksporCsv;
use App\Shared\Infrastructure\Ekspor\PenulisEksporXlsx;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;
use ZipArchive;

/** Isi ekspor berasal dari data tenant. */
final class InjeksiRumusEksporTest extends TestCase
{
    /** @return array<string, array{0: string}> */
    public static function nilaiBerbahaya(): array
    {
        return [
            'sama dengan' => ['=cmd|\' /c calc\'!A0'],
            'tambah' => ['+1+1'],
            'kurang' => ['-1+1'],
            'at' => ['@SUM(A1:A9)'],
            'tab' => ["\t=1+1"],
            'carriage return' => ["\r=1+1"],
        ];
    }

    #[DataProvider('nilaiBerbahaya')]
    public function test_nilai_berawalan_rumus_dinetralkan(string $berbahaya): void
    {
        $isi = $this->tulisCsv(['Label', 'Nilai'], [[$berbahaya, 1]]);

        $this->assertStringContainsString("'".$berbahaya, $isi);
    }

    public function test_nilai_biasa_tidak_diubah(): void
    {
        $isi = $this->tulisCsv(['Label', 'Nilai'], [['Pompa Air Utama', 12]]);

        $this->assertStringContainsString('Pompa Air Utama', $isi);
        $this->assertStringNotContainsString("'Pompa", $isi);
    }

    public function test_kepala_kolom_dan_meta_ikut_dinetralkan(): void
    {
        $isi = $this->tulisCsv(['=Label'], [['aman', 1]], ['=Judul' => '=Nilai']);

        $this->assertStringContainsString("'=Label", $isi);
        $this->assertStringContainsString("'=Judul", $isi);
        $this->assertStringContainsString("'=Nilai", $isi);
    }

    /**
     * Penetralan hanya memeriksa huruf pertama tiap NILAI, jadi ia bersandar
     * pada satu nilai = satu sel. Escape bawaan PHP (`\\`) mematahkan itu:
     * `fputcsv` tidak menggandakan kutip yang didahului backslash, sehingga
     * selnya putus dan potongan sesudahnya dibaca Excel sebagai sel baru --
     * sel yang tidak pernah lewat penetralan.
     *
     * Diperiksa dengan membaca ulang barisnya secara RFC 4180 (tanpa escape
     * backslash), yaitu cara Excel dan LibreOffice membacanya. Assertion pada
     * teks berkas saja tidak cukup: berkas yang bocor pun tetap memuat tanda
     * kutip petik penetralnya di suatu tempat.
     */
    public function test_nilai_berisi_kutip_dan_backslash_tidak_memutus_selnya(): void
    {
        $berbahaya = 'Bor Tulang \\",=cmd|\' /c calc\'!A0';

        $isi = $this->tulisCsv(['Nama', 'Kode'], [[$berbahaya, 'RSUD-001']]);

        $sel = $this->selRfc4180($isi);

        $this->assertCount(2, $sel, 'Satu nilai harus tetap satu sel: '.implode(' | ', $sel));
        $this->assertSame($berbahaya, $sel[0]);
        $this->assertSame('RSUD-001', $sel[1]);
    }

    /** Nilai yang memutus sel DAN berawalan rumus tetap tidak boleh jadi rumus hidup. */
    public function test_sel_pecahan_tidak_pernah_menjadi_rumus_hidup(): void
    {
        $isi = $this->tulisCsv(['Nama'], [['Aset \\",=1+1']]);

        foreach ($this->selRfc4180($isi) as $satu) {
            $this->assertStringStartsNotWith('=', $satu, 'Tidak boleh ada sel yang dibaca Excel sebagai rumus.');
            $this->assertStringStartsNotWith('+', $satu);
            $this->assertStringStartsNotWith('@', $satu);
        }
    }

    /**
     * Baris data pertama, dibaca seperti Excel membacanya.
     *
     * @return list<string>
     */
    private function selRfc4180(string $isi): array
    {
        $baris = explode("\n", trim(str_replace("\xEF\xBB\xBF", '', $isi)));
        $terakhir = (string) end($baris);

        return array_map(strval(...), str_getcsv(rtrim($terakhir, "\r"), ',', '"', ''));
    }

    /**
     * XLSX punya tipe sel, sehingga `+1+1` dan kawannya tersimpan sebagai teks
     * dengan sendirinya. Yang tidak aman dengan sendirinya adalah awalan `=`:
     * OpenSpout mendeteksinya dan menulis sel rumus `<f>` yang sungguhan.
     */
    public function test_xlsx_tidak_pernah_memuat_sel_rumus(): void
    {
        $xml = $this->sheetXlsx(['Label'], [['=cmd|\' /c calc\'!A0'], ['=1+1']]);

        $this->assertStringNotContainsString('<f>', $xml, 'Nilai dari tenant tidak boleh menjadi sel rumus.');
        $this->assertStringContainsString('inlineStr', $xml, 'Nilainya harus tersimpan sebagai teks.');
    }

    public function test_xlsx_tidak_mengubah_nilai_biasa(): void
    {
        $xml = $this->sheetXlsx(['Label'], [['Pompa Air Utama']]);

        $this->assertStringContainsString('Pompa Air Utama', $xml);
        $this->assertStringNotContainsString('&#039;Pompa', $xml);
    }

    /**
     * Isi sheet1.xml, bukan sekadar teks berkasnya: bedanya sel rumus dan sel
     * teks hanya terlihat di XML-nya.
     *
     * @param  list<string>  $kepala
     * @param  list<list<mixed>>  $baris
     */
    private function sheetXlsx(array $kepala, array $baris): string
    {
        $path = tempnam(sys_get_temp_dir(), 'ekspor').'.xlsx';

        try {
            (new PenulisEksporXlsx)->tulis($path, $kepala, $baris, []);

            $zip = new ZipArchive;
            $this->assertTrue($zip->open($path) === true, 'Berkas XLSX tidak dapat dibuka.');
            $xml = (string) $zip->getFromName('xl/worksheets/sheet1.xml');
            $zip->close();

            $this->assertNotSame('', $xml, 'sheet1.xml tidak ditemukan di dalam XLSX.');

            return $xml;
        } finally {
            @unlink($path);
        }
    }

    /**
     * @param  list<string>  $kepala
     * @param  list<list<mixed>>  $baris
     * @param  array<string, mixed>  $meta
     */
    private function tulisCsv(array $kepala, array $baris, array $meta = []): string
    {
        $path = tempnam(sys_get_temp_dir(), 'ekspor').'.csv';

        try {
            (new PenulisEksporCsv)->tulis($path, $kepala, $baris, $meta);

            return (string) file_get_contents($path);
        } finally {
            @unlink($path);
        }
    }
}
