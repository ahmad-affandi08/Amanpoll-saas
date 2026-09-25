<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Setiap tangkapan layar yang dirujuk panduan (`<Tangkapan gambar="...">`) harus punya
 * gambarnya dan posisi setiap penanda yang disebut langkahnya. Tanpa penjaga ini, gambar yang
 * lupa difoto ulang atau penanda yang tidak ketemu setelah tombolnya berganti nama baru
 * ketahuan saat pembaca melihat kotak kosong atau nomor yang hilang.
 */
final class DokumentasiTangkapanTest extends TestCase
{
    private const DIR_ISI = 'resources/js/features/Dokumentasi/components/isi';

    private const DIR_DATA = 'resources/js/features/Dokumentasi/tangkapan';

    private const DIR_GAMBAR = 'public/assets/dokumentasi';

    /**
     * @return array<string, array{0: string, 1: string, 2: list<string>}>
     */
    public static function tangkapanDirujuk(): array
    {
        $hasil = [];

        // Penyedia data dibaca sebelum aplikasi dinyalakan, jadi `base_path()` belum tersedia.
        foreach (glob(dirname(__DIR__, 3).'/'.self::DIR_ISI.'/*.tsx') ?: [] as $berkas) {
            $isi = (string) file_get_contents($berkas);
            $potongan = preg_split('/<Tangkapan\b/', $isi) ?: [];
            array_shift($potongan);

            foreach ($potongan as $blok) {
                // Satu komponen berakhir di `]}` daftar langkah yang langsung disusul `/>`; `/>` saja
                // tidak cukup karena penutup fragmen `</>` di isi langkah juga memuatnya.
                if (preg_match('/^(.*?)\]\}\s*\/>/s', $blok, $cocok) === 1) {
                    $blok = $cocok[1];
                }
                preg_match('/gambar="([^"]+)"/', $blok, $gambar);
                preg_match_all("/penanda: '([^']+)'/", $blok, $penanda);

                $nama = $gambar[1] ?? '(tanpa gambar)';
                $hasil[basename($berkas).' → '.$nama] = [basename($berkas), $nama, array_values(array_unique($penanda[1]))];
            }
        }

        return $hasil;
    }

    public function test_panduan_memakai_tangkapan_layar(): void
    {
        $this->assertNotEmpty(self::tangkapanDirujuk(), 'Tidak ada tangkapan terbaca; pola pembacanya mungkin tidak lagi cocok.');
    }

    /**
     * @param  list<string>  $penanda
     */
    #[DataProvider('tangkapanDirujuk')]
    public function test_gambar_dan_penanda_tersedia(string $halaman, string $gambar, array $penanda): void
    {
        $berkasGambar = base_path(self::DIR_GAMBAR."/{$gambar}.webp");
        $berkasData = base_path(self::DIR_DATA."/{$gambar}.json");

        $this->assertFileExists($berkasGambar, "{$halaman} merujuk gambar {$gambar} yang belum difoto (tools/dokumentasi/tangkap.mjs).");
        $this->assertFileExists($berkasData, "{$halaman}: posisi penanda {$gambar} belum ada.");

        /** @var array{lebar: int, tinggi: int, penanda: array<string, array{x: float, y: float, w: float, h: float}>} $data */
        $data = json_decode((string) file_get_contents($berkasData), true, flags: JSON_THROW_ON_ERROR);

        foreach ($penanda as $kunci) {
            $this->assertArrayHasKey($kunci, $data['penanda'], "{$halaman}: penanda '{$kunci}' tidak ditemukan saat {$gambar} difoto.");

            $kotak = $data['penanda'][$kunci];
            $this->assertTrue(
                $kotak['x'] >= -2 && $kotak['y'] >= -2 && $kotak['x'] + $kotak['w'] <= 102 && $kotak['y'] + $kotak['h'] <= 102,
                "{$halaman}: penanda '{$kunci}' di {$gambar} berada di luar gambar.",
            );
        }
    }
}
