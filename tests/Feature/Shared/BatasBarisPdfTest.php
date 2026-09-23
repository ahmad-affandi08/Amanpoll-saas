<?php

declare(strict_types=1);

namespace Tests\Feature\Shared;

use App\Shared\Infrastructure\Ekspor\PenulisEksporPdf;
use Generator;
use RuntimeException;
use Tests\TestCase;

/**
 * Batas baris PDF berhenti membaca sumbernya.
 *
 * Yang dijaga bukan "PDF-nya terbentuk" -- itu tetap terbentuk walau seluruh
 * daftar dihabiskan lebih dulu. Yang dijaga adalah bahwa penulisnya berhenti
 * meminta baris sesudah BATAS_BARIS + 1, sebab `$baris` lazimnya generator yang
 * menarik potongan dari basis data: membacanya sampai habis pada organisasi
 * dengan 50.000 aset berarti menghidrasi 50.000 model beserta relasinya lalu
 * membuang 48.000 di antaranya, dan di hosting bersama unduhannya kehabisan
 * waktu eksekusi.
 */
final class BatasBarisPdfTest extends TestCase
{
    /** @var list<string> */
    private array $berkasSementara = [];

    protected function tearDown(): void
    {
        foreach ($this->berkasSementara as $berkas) {
            if (is_file($berkas)) {
                unlink($berkas);
            }
        }

        $this->berkasSementara = [];

        parent::tearDown();
    }

    public function test_pembacaan_berhenti_pada_batas_tambah_satu(): void
    {
        $batasAman = PenulisEksporPdf::BATAS_BARIS + 1;
        $diminta = 0;
        $path = $this->pathSementara();

        (new PenulisEksporPdf)->tulis(
            $path,
            ['Nama', 'Kondisi'],
            $this->barisTakTerbatas($batasAman, $diminta),
            ['Judul' => 'Daftar Aset', 'Organisasi' => 'RS Uji'],
        );

        $this->assertSame(
            $batasAman,
            $diminta,
            'Penulis PDF harus berhenti meminta baris pada BATAS_BARIS + 1.',
        );
        $this->assertStringStartsWith('%PDF', (string) file_get_contents($path));
    }

    public function test_daftar_di_bawah_batas_tetap_dibaca_sampai_habis(): void
    {
        $habis = false;
        $path = $this->pathSementara();

        $baris = (function () use (&$habis): Generator {
            yield ['Aset A', 'Baik'];
            yield ['Aset B', 'Rusak Ringan'];

            $habis = true;
        })();

        (new PenulisEksporPdf)->tulis(
            $path,
            ['Nama', 'Kondisi'],
            $baris,
            ['Judul' => 'Daftar Aset', 'Organisasi' => 'RS Uji'],
        );

        $this->assertTrue($habis, 'Daftar yang belum menyentuh batas harus dibaca sampai habis.');
        $this->assertStringStartsWith('%PDF', (string) file_get_contents($path));
    }

    /**
     * Daftar yang tidak pernah habis: baris ke-($batasAman + 1) memprotes alih-alih
     * diberikan, sehingga pembacaan yang kebablasan berhenti sebagai kegagalan test
     * dan bukan sekadar angka yang meleset.
     *
     * @param  int  $batasAman  banyak permintaan yang masih boleh dilayani
     * @param  int  $diminta  dihitung naik tiap kali satu baris diminta
     * @return Generator<int, list<string>>
     */
    private function barisTakTerbatas(int $batasAman, int &$diminta): Generator
    {
        while (true) {
            $diminta++;

            if ($diminta > $batasAman) {
                throw new RuntimeException(
                    "Baris ke-{$diminta} diminta, padahal pembacaan seharusnya sudah berhenti pada baris ke-{$batasAman}.",
                );
            }

            yield ['Aset '.$diminta, 'Baik'];
        }
    }

    private function pathSementara(): string
    {
        $dasar = (string) tempnam(sys_get_temp_dir(), 'uji-batas-pdf');
        $this->berkasSementara[] = $dasar;
        $this->berkasSementara[] = $dasar.'.pdf';

        return $dasar.'.pdf';
    }
}
