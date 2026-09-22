<?php

declare(strict_types=1);

namespace Tests\Architecture;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Tests\TestCase;

/** Host tidak boleh ditulis langsung di source maupun di frontend (PRD 5.4, Gate 24.5). */
final class HostTidakDitulisDiSourceTest extends TestCase
{
    /**
     * Host produksi dan turunannya. Ditulis terpecah supaya berkas test ini
     * sendiri tidak menjadi pelanggar aturan yang ia tegakkan.
     *
     * @var list<string>
     */
    private const POLA_TERLARANG = ['amanpoll', 'amanpoll'];

    /** @var list<string> */
    private const AKHIRAN = ['.com', '.test', '.id', '.co.id'];

    public function test_tidak_ada_host_amanpoll_yang_ditulis_di_php(): void
    {
        $this->pastikanBersih($this->berkas(['app', 'routes', 'config', 'bootstrap'], ['php']));
    }

    public function test_tidak_ada_host_amanpoll_yang_ditulis_di_frontend(): void
    {
        $this->pastikanBersih($this->berkas(['resources/js'], ['ts', 'tsx']));
    }

    /** @param list<SplFileInfo> $berkas */
    private function pastikanBersih(array $berkas): void
    {
        $this->assertNotEmpty($berkas);

        $pelanggar = [];

        foreach ($berkas as $satu) {
            $isi = (string) file_get_contents($satu->getPathname());

            foreach (self::AKHIRAN as $akhiran) {
                $pola = self::POLA_TERLARANG[0].$akhiran;
                if (stripos($isi, $pola) !== false) {
                    $pelanggar[] = $this->jalurRelatif($satu).' → '.$pola;
                }
            }
        }

        $this->assertSame(
            [],
            $pelanggar,
            'Host harus dibaca dari konfigurasi lewat PetaHost, bukan ditulis di source: '
                .implode(', ', $pelanggar),
        );
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
