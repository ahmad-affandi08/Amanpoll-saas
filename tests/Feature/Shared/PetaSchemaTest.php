<?php

declare(strict_types=1);

namespace Tests\Feature\Shared;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Menjaga docs/PETA-SCHEMA.md tetap sama dengan schema yang sebenarnya.
 *
 * Dokumen itu pernah tertinggal 79 tabel karena tidak ada yang memberi tahu
 * saat migrasi baru ditambahkan. Peta schema yang salah lebih berbahaya
 * daripada yang tidak ada: pembacanya menyangka sudah tahu isinya.
 */
class PetaSchemaTest extends TestCase
{
    use RefreshDatabase;

    /** @return list<string> */
    private function tabelBasisData(): array
    {
        $nama = array_map(
            static fn (object $baris): string => (string) array_values((array) $baris)[0],
            DB::select('SHOW TABLES'),
        );

        $nama = array_values(array_filter($nama, static fn (string $satu): bool => $satu !== 'migrations'));
        sort($nama);

        return $nama;
    }

    /** @return list<string> */
    private function tabelDokumen(): array
    {
        $isi = (string) file_get_contents(base_path('docs/PETA-SCHEMA.md'));
        preg_match_all('/^- `([A-Za-z]+)`$/m', $isi, $cocok);

        $nama = $cocok[1];
        sort($nama);

        return $nama;
    }

    public function test_peta_schema_memuat_persis_tabel_yang_ada(): void
    {
        $basisData = $this->tabelBasisData();
        $dokumen = $this->tabelDokumen();

        $kurang = array_values(array_diff($basisData, $dokumen));
        $berlebih = array_values(array_diff($dokumen, $basisData));

        $this->assertSame(
            [],
            $kurang,
            'Tabel ini ada di basis data tetapi belum dicatat di docs/PETA-SCHEMA.md: '.implode(', ', $kurang),
        );
        $this->assertSame(
            [],
            $berlebih,
            'Tabel ini dicatat di docs/PETA-SCHEMA.md tetapi tidak ada di basis data: '.implode(', ', $berlebih),
        );
    }

    public function test_jumlah_yang_ditulis_di_kepala_dokumen_benar(): void
    {
        $isi = (string) file_get_contents(base_path('docs/PETA-SCHEMA.md'));

        preg_match('/Total tabel: \*\*(\d+)\*\*, ditambah (\d+) view\./', $isi, $cocok);
        $this->assertCount(3, $cocok, 'Baris jumlah di kepala PETA-SCHEMA.md tidak terbaca lagi.');

        $semua = $this->tabelBasisData();
        $view = array_filter($semua, static fn (string $satu): bool => str_starts_with($satu, 'View'));

        $this->assertSame(count($semua) - count($view), (int) $cocok[1]);
        $this->assertSame(count($view), (int) $cocok[2]);
    }
}
