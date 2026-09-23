<?php

declare(strict_types=1);

namespace Tests\Architecture;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Tests\TestCase;

/**
 * Tanggal kalender tidak boleh dibaca dari jam aplikasi yang UTC (FASE 26, Timezone).
 *
 * Aplikasi berjalan dan menyimpan dalam UTC, sedangkan "hari ini", "jatuh
 * tempo", dan "awal bulan" milik kalender rumah sakitnya. Setiap pola di bawah
 * menghasilkan tanggal UTC -- yang selama tujuh jam pertama tiap hari WIB masih
 * tanggal kemarin -- atau menyaring kolom waktu dengan batas tengah malam UTC.
 * Semua titik yang ada sudah dipindahkan ke `KalenderOrganisasi`,
 * `FilterMetrik`, atau jam berzona eksplisit; test ini menjaga supaya kode baru
 * tidak mengembalikannya diam-diam.
 */
final class TanggalKalenderTidakDariJamUtcTest extends TestCase
{
    /**
     * Pola terlarang => cara yang benar.
     *
     * @var array<string, string>
     */
    private const POLA_PHP = [
        '/\bCarbon(Immutable)?::today\(\)/' => 'KalenderOrganisasi::hariIni()',
        '/(?<![\w>:$])today\(\)/' => 'KalenderOrganisasi::hariIni()',
        '/(?<![\w>:$])now\(\)->(toDateString|startOfDay|endOfDay|startOfMonth|startOfYear)\(/' => 'KalenderOrganisasi::hariIni() atau awalHari()',
        '/(?<![\w>:$])now\(\)->format\(\'[^\']*[YmdyjnHi]/' => 'KalenderOrganisasi::sekarang()->format()',
        '/\bCarbon(Immutable)?::now\(\)(->sub\w+\([^)]*\))?->(toDateString|startOfDay|endOfDay|startOfMonth|startOfYear|format)\(/' => 'KalenderOrganisasi atau now($zona)',
        '/\b(CURRENT_DATE|CURDATE\(\)|CURRENT_TIMESTAMP\(\)|NOW\(\))/' => 'tanggal hari ini yang diikat sebagai parameter',
        '/whereDate\(\s*[\'"][A-Za-z.]*Pada[\'"]/' => 'rentang awalHari() .. awalHariBerikutnya()',
        '/DATE\(\s*[A-Za-z.]*Pada\s*\)/' => 'DATE(CONVERT_TZ(kolom, \'+00:00\', offsetSql()))',
    ];

    /** Satu-satunya tempat yang memang boleh menyentuh jam mentah untuk membangun kalender. */
    private const DIKECUALIKAN = [
        'app/Core/Organisasi/KalenderOrganisasi.php',
    ];

    public function test_tanggal_kalender_tidak_dibaca_dari_jam_utc_di_php(): void
    {
        $berkas = $this->berkas(['app', 'routes'], ['php']);
        $this->assertNotEmpty($berkas);

        $pelanggar = [];
        foreach ($berkas as $satu) {
            $jalur = $this->jalurRelatif($satu);
            if (in_array($jalur, self::DIKECUALIKAN, true)) {
                continue;
            }

            foreach (file($satu->getPathname()) ?: [] as $nomor => $baris) {
                foreach (self::POLA_PHP as $pola => $gantinya) {
                    if (preg_match($pola, $baris) === 1) {
                        $pelanggar[] = sprintf('%s:%d → pakai %s', $jalur, $nomor + 1, $gantinya);
                    }
                }
            }
        }

        $this->assertSame([], $pelanggar, "Tanggal kalender dibaca dari jam UTC:\n".implode("\n", $pelanggar));
    }

    /**
     * Input `datetime-local` berbicara jam dinding tanpa zona; dikirim apa
     * adanya, server membacanya sebagai jam UTC. Setiap formulir yang memakainya
     * harus mengonversi lewat `@/lib/waktu` (atau `toISOString()`).
     */
    public function test_masukan_datetime_local_dikonversi_sebelum_dikirim(): void
    {
        $pelanggar = [];
        foreach ($this->berkas(['resources/js'], ['tsx']) as $satu) {
            $isi = (string) file_get_contents($satu->getPathname());
            if (! str_contains($isi, 'datetime-local')) {
                continue;
            }

            if (! str_contains($isi, 'dariMasukanWaktu') && ! str_contains($isi, 'toISOString()')) {
                $pelanggar[] = $this->jalurRelatif($satu);
            }

            if (preg_match('/\?\.slice\(0,\s*16\)/', $isi) === 1) {
                $pelanggar[] = $this->jalurRelatif($satu).' (memotong ISO UTC untuk isi awal; pakai keMasukanWaktu)';
            }
        }

        $this->assertSame([], $pelanggar, "Masukan datetime-local tidak dikonversi:\n".implode("\n", $pelanggar));
    }

    /**
     * `toISOString()` berbicara UTC. Memotongnya menjadi tanggal memberi tanggal
     * UTC -- sebelum pukul 07:00 WIB masih kemarin -- baik untuk nilai awal
     * "hari ini" maupun untuk menampilkan tanggal sebuah momen dari server.
     */
    public function test_tanggal_di_frontend_tidak_dipotong_dari_iso_utc(): void
    {
        $pola = [
            '/toISOString\(\)\.(slice|substring)\(0,\s*10\)/' => 'tanggalHariIni() / tanggalLokal()',
            '/toISOString\(\)\.split\(/' => 'tanggalHariIni() / tanggalLokal()',
            '/Pada\??\.(slice|substring)\(0,\s*(10|16)\)/' => 'tanggalLokal() / keMasukanWaktu()',
        ];

        $pelanggar = [];
        foreach ($this->berkas(['resources/js'], ['ts', 'tsx']) as $satu) {
            $jalur = $this->jalurRelatif($satu);
            if ($jalur === 'resources/js/lib/waktu.ts') {
                continue;
            }

            foreach (file($satu->getPathname()) ?: [] as $nomor => $baris) {
                foreach ($pola as $satuPola => $gantinya) {
                    if (preg_match($satuPola, $baris) === 1) {
                        $pelanggar[] = sprintf('%s:%d → pakai %s', $jalur, $nomor + 1, $gantinya);
                    }
                }
            }
        }

        $this->assertSame([], $pelanggar, "Tanggal dipotong dari ISO UTC:\n".implode("\n", $pelanggar));
    }

    /**
     * @param  list<string>  $direktori
     * @param  list<string>  $ekstensi
     * @return list<SplFileInfo>
     */
    private function berkas(array $direktori, array $ekstensi): array
    {
        $hasil = [];

        foreach ($direktori as $satu) {
            $akar = base_path($satu);
            if (! is_dir($akar)) {
                continue;
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($akar, FilesystemIterator::SKIP_DOTS),
            );

            foreach ($iterator as $berkas) {
                if ($berkas instanceof SplFileInfo && in_array($berkas->getExtension(), $ekstensi, true)) {
                    $hasil[] = $berkas;
                }
            }
        }

        return $hasil;
    }

    private function jalurRelatif(SplFileInfo $berkas): string
    {
        return str_replace(base_path().'/', '', $berkas->getPathname());
    }
}
