<?php

declare(strict_types=1);

namespace Tests\Architecture;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Tests\TestCase;

/**
 * Nominal uang diisi lewat `InputUang`, bukan `<Input type="number">`.
 *
 * Isian angka mentah menampilkan `1500000` tanpa pemisah dan tanpa lambang,
 * sehingga salah ketik satu nol pada harga atau anggaran tidak terlihat.
 * Kolom yang namanya jelas bernilai uang dikenali dari namanya; `Jumlah`
 * sengaja tidak ikut karena dipakai untuk kuantitas dan nominal sekaligus.
 */
final class NominalUangMemakaiInputUangTest extends TestCase
{
    private const KOLOM_UANG = '(Harga\w*|Biaya\w*|Budget|Subtotal|Pajak|Diskon|NilaiBuku\w*|NilaiResidu|'
        .'AkumulasiPenyusutan|BebanPenyusutan\w*|HasilPelepasan|MaksPembayaran|EstimasiHarga\w*)';

    public function test_isian_nominal_uang_tidak_memakai_input_angka_mentah(): void
    {
        $pelanggar = [];

        foreach ($this->berkas() as $satu) {
            $isi = (string) file_get_contents($satu->getPathname());
            preg_match_all('/<Input\b(?:[^>]|=>)*?\/>/s', $isi, $cocok, PREG_OFFSET_CAPTURE);

            foreach ($cocok[0] as [$blok, $posisi]) {
                if (! str_contains($blok, 'type="number"')) {
                    continue;
                }

                if (preg_match('/(setData\(\s*\'|form\.data\.|ubahBaris\([^,]+,\s*\')'.self::KOLOM_UANG.'\b/', $blok, $kolom) === 1) {
                    $baris = substr_count(substr($isi, 0, $posisi), "\n") + 1;
                    $pelanggar[] = sprintf('%s:%d (%s)', str_replace(base_path().'/', '', $satu->getPathname()), $baris, $kolom[2]);
                }
            }
        }

        $this->assertSame([], $pelanggar, "Nominal uang memakai <Input type=\"number\">; pakai InputUang:\n".implode("\n", $pelanggar));
    }

    /** @return list<SplFileInfo> */
    private function berkas(): array
    {
        $hasil = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(base_path('resources/js'), FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $berkas) {
            if ($berkas instanceof SplFileInfo && $berkas->getExtension() === 'tsx') {
                $hasil[] = $berkas;
            }
        }

        $this->assertNotEmpty($hasil);

        return $hasil;
    }
}
