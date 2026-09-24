<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Core\Izin\LingkupAkses;
use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\Gudang;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Data contoh dua unit pengelola (TASK 40.06): bagian Teknik & Fasilitas dan IT
 * dengan aset, kategori keluhan, gudang, dan pengguna berlingkup masing-masing.
 */
class SeederUnitPengelolaTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        app(KonteksOrganisasi::class)->bersihkan();

        parent::tearDown();
    }

    public function test_seeder_menyemai_dua_unit_pengelola_lengkap_dan_aman_diulang(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $organisasiId = (string) DB::table('Organisasi')->where('Kode', 'AMANPOLL')->value('Id');
        $unit = DB::table('UnitOrganisasi')->where('OrganisasiId', $organisasiId)->where('MengelolaAset', true)->pluck('Id', 'Kode');

        $this->assertSame(['IT', 'TEKFAS'], $unit->keys()->sort()->values()->all());

        foreach ($unit as $unitId) {
            foreach (['Aset', 'KategoriKeluhan', 'Gudang'] as $tabel) {
                $this->assertSame(1, DB::table($tabel)->where('UnitPengelolaId', $unitId)->count(), "{$tabel} per unit pengelola");
            }

            $this->assertSame(2, DB::table('PenggunaPeran')->where('UnitOrganisasiId', $unitId)->count(), 'Koordinator dan teknisi berlingkup unit');
        }

        $this->assertSame(1, DB::table('Pengguna')->where('Email', 'koordinator.it@amanpoll.test')->count());
    }

    public function test_koordinator_it_contoh_berlingkup_dan_hanya_melihat_gudang_it(): void
    {
        $this->seed(DatabaseSeeder::class);

        $koordinator = Pengguna::query()->where('Email', 'koordinator.it@amanpoll.test')->firstOrFail();
        app(KonteksOrganisasi::class)->tetapkan((string) $koordinator->OrganisasiId);

        $this->assertFalse(app(LingkupAkses::class)->tanpaBatas((string) $koordinator->Id));

        $this->actingAs($koordinator, 'web');
        $this->assertSame(['Gudang IT'], Gudang::query()->pluck('Nama')->all());
    }
}
