<?php

declare(strict_types=1);

namespace Tests\Architecture;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Tests\TestCase;

/**
 * Kosakata nilai domain (status, jenis, prioritas, dan sejenisnya) hidup sebagai
 * enum di `Domain/Enums`, bukan sebagai konstanta string di model.
 *
 * Dua bentuk yang berdampingan adalah bagaimana satu nilai bisa punya dua daftar
 * yang berbeda; tes ini menjaga agar yang sudah diseragamkan tidak kembali
 * terpecah.
 */
final class KosakataDomainTest extends TestCase
{
    /**
     * Konstanta model yang memang bukan kosakata domain: pemetaan kolom
     * timestamp milik Eloquent.
     *
     * @var list<string>
     */
    private const DIKECUALIKAN = ['CREATED_AT', 'UPDATED_AT', 'DELETED_AT'];

    public function test_model_tidak_lagi_menyimpan_kosakata_domain_sebagai_konstanta(): void
    {
        $pelanggar = [];

        foreach ($this->berkasModel() as $berkas) {
            $isi = (string) file_get_contents($berkas->getPathname());

            preg_match_all("/^\s*public const ([A-Z][A-Z0-9_]*) = ['\[]/m", $isi, $cocok);

            $konstanta = array_values(array_diff($cocok[1], self::DIKECUALIKAN));

            if ($konstanta !== []) {
                $pelanggar[$berkas->getBasename('.php')] = $konstanta;
            }
        }

        $this->assertSame(
            [],
            $pelanggar,
            'Model berikut masih memakai konstanta untuk kosakata domain; pindahkan ke enum di Domain/Enums: '
                .json_encode($pelanggar, JSON_UNESCAPED_SLASHES),
        );
    }

    public function test_setiap_enum_domain_adalah_enum_berbasis_string(): void
    {
        $berkas = glob(base_path('app/Domain/*/Domain/Enums/*.php'));
        $this->assertNotEmpty($berkas);

        foreach ($berkas as $satu) {
            $isi = (string) file_get_contents($satu);
            $nama = basename($satu, '.php');

            $this->assertMatchesRegularExpression(
                "/^enum {$nama}: string$/m",
                $isi,
                "{$nama} harus berupa enum berbasis string agar nilainya dapat disimpan dan divalidasi.",
            );
        }
    }

    /** @return list<SplFileInfo> */
    private function berkasModel(): array
    {
        $berkas = [];

        foreach (glob(base_path('app/Domain/*/Infrastructure/Persistence/Models'), GLOB_ONLYDIR) as $direktori) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($direktori, FilesystemIterator::SKIP_DOTS),
            );

            foreach ($iterator as $satu) {
                if ($satu instanceof SplFileInfo && $satu->getExtension() === 'php') {
                    $berkas[] = $satu;
                }
            }
        }

        $this->assertNotEmpty($berkas);

        return $berkas;
    }
}
