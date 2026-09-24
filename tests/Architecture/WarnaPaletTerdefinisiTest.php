<?php

declare(strict_types=1);

namespace Tests\Architecture;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Tests\TestCase;

/**
 * Setiap kelas warna palet Amanpoll yang dipakai harus punya token di `@theme`.
 *
 * Tailwind v4 membangun kelas warna dari token `--color-*`. Kelas yang menunjuk
 * shade tanpa token (`border-permukaan-200`, `hover:bg-teknisi-100`) tidak
 * menghasilkan CSS apa pun dan tidak menggagalkan build, jadi border, latar
 * hover, dan warna teksnya hilang diam-diam. Sebelum test ini ada, sekitar 200
 * pemakaian seperti itu tersebar di pemilih tanggal, daftar periksa, inspeksi,
 * dan kalibrasi.
 *
 * Palet Mode Lapangan (`lapangan-*`, DESIGN.md 36.4) ikut diperiksa. Namanya tidak
 * selalu berakhiran angka (`lapangan-teks-2`, `lapangan-oranye-teks`,
 * `lapangan-latar`), jadi ia punya pola sendiri yang mencocokkan nama token utuh.
 */
final class WarnaPaletTerdefinisiTest extends TestCase
{
    private const PALET = ['teknisi', 'safety', 'sukses', 'bahaya', 'info', 'grafit', 'garis', 'permukaan'];

    public function test_kelas_warna_palet_hanya_menunjuk_token_yang_terdefinisi(): void
    {
        $css = (string) file_get_contents(resource_path('css/app.css'));
        preg_match_all('/--color-([a-z]+-\d+)\s*:/', $css, $cocok);
        $terdefinisi = array_flip($cocok[1]);
        $this->assertNotEmpty($terdefinisi);

        $pola = '/(?<![\w-])(?:[a-z0-9-]+:)*(?:bg|text|border(?:-[trblxy])?|ring(?:-offset)?|outline|divide|fill|stroke|from|via|to|shadow|placeholder|decoration|caret|accent)-('
            .implode('|', self::PALET).')-(\d+)(?:\/\d+)?(?![\w-])/';

        $pelanggar = [];
        foreach ($this->berkas() as $satu) {
            foreach (file($satu->getPathname()) ?: [] as $nomor => $baris) {
                preg_match_all($pola, $baris, $temuan, PREG_SET_ORDER);
                foreach ($temuan as $t) {
                    $token = $t[1].'-'.$t[2];
                    if (! isset($terdefinisi[$token])) {
                        $pelanggar[] = sprintf('%s:%d → %s', str_replace(base_path().'/', '', $satu->getPathname()), $nomor + 1, $token);
                    }
                }
            }
        }

        $this->assertSame([], $pelanggar, "Kelas warna tanpa token di resources/css/app.css:\n".implode("\n", $pelanggar));
    }

    public function test_kelas_warna_lapangan_hanya_menunjuk_token_yang_terdefinisi(): void
    {
        $css = (string) file_get_contents(resource_path('css/app.css'));
        preg_match_all('/--color-(lapangan(?:-[a-z0-9]+)+)\s*:/', $css, $warna);
        preg_match_all('/--shadow-(lapangan(?:-[a-z0-9]+)+)\s*:/', $css, $bayangan);
        $tokenWarna = array_flip($warna[1]);
        $tokenBayangan = array_flip($bayangan[1]);
        $this->assertNotEmpty($tokenWarna);

        $pola = '/(?<![\w-])(?:[a-z0-9-]+:)*(bg|text|border(?:-[trblxy])?|ring(?:-offset)?|outline|divide|fill|stroke|from|via|to|shadow|placeholder|decoration|caret|accent)-'
            .'(lapangan(?:-[a-z0-9]+)+)(?:\/\d+)?(?![\w-])/';

        $diperiksa = 0;
        $pelanggar = [];
        foreach ($this->berkas() as $satu) {
            foreach (file($satu->getPathname()) ?: [] as $nomor => $baris) {
                preg_match_all($pola, $baris, $temuan, PREG_SET_ORDER);
                foreach ($temuan as $t) {
                    $diperiksa++;
                    // `shadow-lapangan-kartu` menunjuk token bayangan; `shadow-lapangan-oranye-700` menunjuk warna.
                    $sah = isset($tokenWarna[$t[2]]) || ($t[1] === 'shadow' && isset($tokenBayangan[$t[2]]));
                    if (! $sah) {
                        $pelanggar[] = sprintf('%s:%d → %s-%s', str_replace(base_path().'/', '', $satu->getPathname()), $nomor + 1, $t[1], $t[2]);
                    }
                }
            }
        }

        // Pola yang tidak pernah cocok akan lolos tanpa memeriksa apa pun.
        $this->assertGreaterThan(0, $diperiksa, 'Tidak ada kelas lapangan-* yang terbaca; pola pemeriksaannya rusak.');
        $this->assertSame([], $pelanggar, "Kelas warna lapangan-* tanpa token di resources/css/app.css:\n".implode("\n", $pelanggar));
    }

    /** @return list<SplFileInfo> */
    private function berkas(): array
    {
        $hasil = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(base_path('resources/js'), FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $berkas) {
            if ($berkas instanceof SplFileInfo && in_array($berkas->getExtension(), ['ts', 'tsx'], true)) {
                $hasil[] = $berkas;
            }
        }

        $this->assertNotEmpty($hasil);

        return $hasil;
    }
}
