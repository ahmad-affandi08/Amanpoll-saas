<?php

declare(strict_types=1);

namespace Tests\Architecture;

use Tests\TestCase;

/**
 * Menjaga agar tidak ada assertion mati sesudah expectException().
 *
 * expectException() tidak menghentikan apa pun: begitu kode yang diuji
 * melempar, lemparannya melompat keluar dari method test, sehingga setiap
 * baris sesudahnya tidak pernah dieksekusi. Test tetap hijau, dan pembacanya
 * menyangka pemeriksaan di bawahnya ikut berjalan.
 *
 * Pola ini pernah membuat dua penjaga hampa di repo ini: satu memeriksa baris
 * mass assignment lintas organisasi tidak tertulis, satu lagi memeriksa
 * peristiwa pemasaran tidak terhapus. Keduanya tidak pernah dijalankan.
 *
 * Yang benar: tangkap dengan try/catch lalu $this->fail(), sehingga
 * pemeriksaan sesudahnya tetap jalan.
 */
class AssertionSetelahExpectExceptionTest extends TestCase
{
    public function test_tidak_ada_assertion_sesudah_expect_exception(): void
    {
        $pelanggar = [];

        foreach ($this->berkasTest() as $berkas) {
            $baris = (array) file($berkas);
            $menunggu = null;

            foreach ($baris as $urutan => $isi) {
                $isi = (string) $isi;

                // Batas method: penantian dari method sebelumnya tidak boleh bocor.
                if (preg_match('/^    (public|private|protected)\s+function /', $isi) === 1) {
                    $menunggu = null;
                }

                if (str_contains($isi, '->expectException(')) {
                    $menunggu = $urutan + 1;

                    continue;
                }

                if ($menunggu !== null && preg_match('/\$this->assert[A-Za-z]+\(/', $isi) === 1) {
                    $pelanggar[] = $this->jalurRelatif($berkas).':'.($urutan + 1)
                        .' (expectException di baris '.$menunggu.')';
                    $menunggu = null;
                }
            }
        }

        $this->assertSame(
            [],
            $pelanggar,
            'Assertion berikut tidak pernah dieksekusi karena ada expectException() di atasnya; '
                .'ganti dengan try/catch + $this->fail(): '.implode(', ', $pelanggar),
        );
    }

    /** @return list<string> */
    private function berkasTest(): array
    {
        $berkas = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(base_path('tests'), \FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $satu) {
            // Berkas ini sendiri dilewati: kata kunci yang dicarinya ada di
            // source pemindainya, jadi ia akan selalu menuduh dirinya sendiri.
            if ($satu instanceof \SplFileInfo
                && $satu->getExtension() === 'php'
                && $satu->getPathname() !== __FILE__
            ) {
                $berkas[] = $satu->getPathname();
            }
        }

        sort($berkas);

        // Tanpa ini penjaganya diam-diam hampa saat direktorinya bergeser.
        $this->assertNotEmpty($berkas);

        return $berkas;
    }

    private function jalurRelatif(string $berkas): string
    {
        return str_replace(base_path().'/', '', $berkas);
    }
}
