<?php

declare(strict_types=1);

namespace Tests\Feature\Core;

use App\Shared\Infrastructure\Persistence\BatasDaftar;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Batas daftar hidup di dua tempat dan harus tetap sama (FASE 25.01).
 *
 * Server memotong pada `BatasDaftar::MAKS`, dan DataTable memutuskan kapan
 * memberi tahu pengguna bahwa daftarnya terpotong dengan membandingkan jumlah
 * baris terhadap `BATAS_DAFTAR`. Kalau keduanya bergeser sendiri-sendiri,
 * pemberitahuannya berhenti muncul tepat ketika ia paling dibutuhkan.
 */
final class BatasDaftarTest extends TestCase
{
    public function test_batas_di_php_dan_typescript_bernilai_sama(): void
    {
        $ts = File::get(resource_path('js/lib/batas.ts'));

        $cocok = preg_match('/export const BATAS_DAFTAR = (\d+);/', $ts, $bagian);

        $this->assertSame(1, $cocok, 'BATAS_DAFTAR tidak ditemukan di resources/js/lib/batas.ts.');
        $this->assertSame(
            BatasDaftar::MAKS,
            (int) $bagian[1],
            'BatasDaftar::MAKS dan BATAS_DAFTAR sudah tidak sama.',
        );
    }

    /** Batas yang terlalu kecil memotong daftar wajar; yang terlalu besar tidak menahan apa pun. */
    public function test_batas_berada_pada_rentang_yang_masuk_akal(): void
    {
        $this->assertGreaterThanOrEqual(100, BatasDaftar::MAKS);
        $this->assertLessThanOrEqual(2000, BatasDaftar::MAKS);
    }

    /** Komponen DataTable-lah yang memberi tahu pengguna; tanpa itu pemotongan terjadi diam-diam. */
    public function test_datatable_memberi_tahu_saat_daftar_terpotong(): void
    {
        $komponen = File::get(resource_path('js/components/data-table/DataTable.tsx'));

        $this->assertStringContainsString('BATAS_DAFTAR', $komponen);
        $this->assertStringContainsString('Daftar dibatasi', $komponen);
    }
}
