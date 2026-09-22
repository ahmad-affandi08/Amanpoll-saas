<?php

declare(strict_types=1);

namespace Tests\Architecture;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Tests\TestCase;

/** Setiap `Inertia::render('Fitur/Halaman')` harus punya berkas komponennya. */
final class HalamanInertiaAdaTest extends TestCase
{
    public function test_setiap_komponen_yang_dirender_memiliki_berkasnya(): void
    {
        $hilang = [];

        foreach ($this->berkasPhp() as $berkas) {
            $isi = (string) file_get_contents($berkas->getPathname());

            preg_match_all("/Inertia::render\(\s*'([A-Za-z0-9\/_-]+)'/", $isi, $cocok);

            foreach ($cocok[1] as $komponen) {
                if (! is_file($this->jalurKomponen($komponen))) {
                    $hilang[] = $komponen.' (dirender di '.$this->jalurRelatif($berkas).')';
                }
            }
        }

        $this->assertSame(
            [],
            $hilang,
            'Komponen Inertia berikut dirender tetapi berkasnya tidak ada: '.implode(', ', $hilang),
        );
    }

    private function jalurKomponen(string $komponen): string
    {
        $bagian = explode('/', $komponen);
        $fitur = array_shift($bagian);

        return resource_path('js/features/'.$fitur.'/pages/'.implode('/', $bagian).'.tsx');
    }

    /**
     * Hanya controller yang dipindai. Contoh di dalam docblock — misalnya pada
     * view finder — bukan render yang sungguhan dan tidak boleh ikut dihitung.
     *
     * @return list<SplFileInfo>
     */
    private function berkasPhp(): array
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(app_path(), FilesystemIterator::SKIP_DOTS),
        );

        $berkas = [];
        foreach ($iterator as $satu) {
            if ($satu instanceof SplFileInfo
                && $satu->getExtension() === 'php'
                && str_contains($satu->getPathname(), '/Http/Controllers/')) {
                $berkas[] = $satu;
            }
        }

        $this->assertNotEmpty($berkas);

        return $berkas;
    }

    private function jalurRelatif(SplFileInfo $berkas): string
    {
        return str_replace(base_path().'/', '', $berkas->getPathname());
    }
}
