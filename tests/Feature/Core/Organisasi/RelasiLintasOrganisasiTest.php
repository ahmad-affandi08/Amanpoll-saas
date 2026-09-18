<?php

declare(strict_types=1);

namespace Tests\Feature\Core\Organisasi;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\KunciApi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\UnitOrganisasi;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RelasiLintasOrganisasiTest extends TestCase
{
    use RefreshDatabase;

    private function buatOrganisasi(string $kode): Organisasi
    {
        return Organisasi::create(['Kode' => $kode, 'Nama' => "Organisasi {$kode}"]);
    }

    public function test_menolak_unit_organisasi_menunjuk_induk_milik_organisasi_lain(): void
    {
        $organisasiA = $this->buatOrganisasi('ORG-A');
        $organisasiB = $this->buatOrganisasi('ORG-B');

        $konteks = app(KonteksOrganisasi::class);

        $konteks->tetapkan($organisasiB->Id);
        $unitB = UnitOrganisasi::create(['Kode' => 'UNIT-B', 'Nama' => 'Unit B']);
        $konteks->bersihkan();

        $konteks->tetapkan($organisasiA->Id);
        $this->expectException(AturanBisnisDilanggar::class);
        UnitOrganisasi::create(['Kode' => 'UNIT-A', 'Nama' => 'Unit A', 'IndukId' => $unitB->Id]);
    }

    public function test_mengizinkan_unit_organisasi_menunjuk_induk_milik_organisasi_sendiri(): void
    {
        $organisasiA = $this->buatOrganisasi('ORG-A');

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasiA->Id);

        $induk = UnitOrganisasi::create(['Kode' => 'UNIT-INDUK', 'Nama' => 'Unit Induk']);
        $anak = UnitOrganisasi::create(['Kode' => 'UNIT-ANAK', 'Nama' => 'Unit Anak', 'IndukId' => $induk->Id]);

        $this->assertSame($induk->Id, $anak->IndukId);
    }

    /**
     * Koreksi ADR 0002 bagian 5: guard ini berbasis metadata skema
     * (Schema::hasColumn target, bukan penggunaan trait MilikOrganisasi di
     * model target), jadi tetap berlaku untuk FK ke Pengguna karena tabel
     * Pengguna sendiri punya kolom OrganisasiId -- meski model Pengguna
     * sengaja tidak memakai trait MilikOrganisasi (lihat bagian 5).
     */
    public function test_menolak_kunci_api_menunjuk_pembuat_milik_organisasi_lain(): void
    {
        $organisasiA = $this->buatOrganisasi('ORG-A');
        $organisasiB = $this->buatOrganisasi('ORG-B');

        $penggunaB = Pengguna::create([
            'OrganisasiId' => $organisasiB->Id,
            'Nama' => 'Pengguna B',
            'Email' => 'b@amanpoll.test',
            'KataSandi' => 'rahasia',
            'Status' => 'Aktif',
        ]);

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasiA->Id);

        $this->expectException(AturanBisnisDilanggar::class);
        KunciApi::create([
            'Nama' => 'Kunci Retas',
            'AwalanKunci' => 'RETAS123456',
            'HashKunci' => hash('sha256', 'apapun'),
            'Status' => 'Aktif',
            'DibuatOleh' => $penggunaB->Id,
        ]);
    }
}
