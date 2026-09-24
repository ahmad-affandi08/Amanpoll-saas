<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Core\Izin\LingkupAkses;
use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Pemeliharaan\Domain\Enums\StatusKeluhan;
use App\Domain\Pemeliharaan\Domain\Enums\StatusPerintahKerja;
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

        foreach ($unit as $kode => $unitId) {
            foreach (['Aset', 'KategoriKeluhan'] as $tabel) {
                $this->assertSame(1, DB::table($tabel)->where('UnitPengelolaId', $unitId)->count(), "{$tabel} per unit pengelola");
            }

            // Teknik & Fasilitas juga memegang gudang suku cadang utama.
            $this->assertSame($kode === 'TEKFAS' ? 2 : 1, DB::table('Gudang')->where('UnitPengelolaId', $unitId)->count(), "Gudang {$kode}");

            $this->assertSame(2, DB::table('PenggunaPeran')->where('UnitOrganisasiId', $unitId)->count(), 'Koordinator dan teknisi berlingkup unit');
        }

        $this->assertSame(1, DB::table('Pengguna')->where('Email', 'koordinator.it@amanpoll.test')->count());
    }

    /**
     * Tidak ada data contoh yang menunjuk unit tak bertanda atau jatuh tanpa
     * unit pengelola. Keluhan dan perintah kerja hanya dihitung yang belum
     * final dan belum dihapus: riwayat tiket final boleh menyebut unit lama.
     */
    public function test_seluruh_data_contoh_menunjuk_unit_pengelola_bertanda_yang_sesuai(): void
    {
        $this->seed(DatabaseSeeder::class);

        $organisasiId = (string) DB::table('Organisasi')->where('Kode', 'AMANPOLL')->value('Id');
        $unit = DB::table('UnitOrganisasi')->where('OrganisasiId', $organisasiId)->where('MengelolaAset', true)->pluck('Id', 'Kode');

        // Pembanding: tabel yang memang disemai tidak kosong, jadi nol di bawah bukan karena tidak ada baris.
        foreach (['Aset' => 2, 'KategoriKeluhan' => 2, 'Gudang' => 3] as $tabel => $jumlah) {
            $this->assertSame($jumlah, DB::table($tabel)->where('OrganisasiId', $organisasiId)->count(), "Baris contoh {$tabel}");
        }

        foreach (['Aset', 'KategoriKeluhan', 'Gudang', 'RencanaPemeliharaan', 'RencanaKalibrasi', 'Keluhan', 'PerintahKerja'] as $tabel) {
            $tanpaUnitBertanda = DB::table($tabel)
                ->where('OrganisasiId', $organisasiId)
                ->when(in_array($tabel, ['Aset', 'Keluhan', 'PerintahKerja'], true), fn ($kueri) => $kueri->whereNull('DihapusPada'))
                ->when($tabel === 'Keluhan', fn ($kueri) => $kueri->whereNotIn('Status', $this->statusFinal(StatusKeluhan::cases())))
                ->when($tabel === 'PerintahKerja', fn ($kueri) => $kueri->whereNotIn('Status', $this->statusFinal(StatusPerintahKerja::cases())))
                ->where(fn ($kueri) => $kueri->whereNull('UnitPengelolaId')->orWhereNotIn('UnitPengelolaId', $unit->values()->all()))
                ->count();

            $this->assertSame(0, $tanpaUnitBertanda, "{$tabel} tanpa unit pengelola bertanda");
        }

        $unitMenurutKode = fn (string $tabel, string $kolomKode, string $kode): mixed => DB::table($tabel)
            ->where('OrganisasiId', $organisasiId)->where($kolomKode, $kode)->value('UnitPengelolaId');

        $this->assertSame($unit['IT'], $unitMenurutKode('Aset', 'KodeAset', 'AST-IT-001'), 'Printer dipelihara IT');
        $this->assertSame($unit['TEKFAS'], $unitMenurutKode('Aset', 'KodeAset', 'AST-FAS-001'), 'Genset dipelihara Teknik & Fasilitas');
        $this->assertSame($unit['IT'], $unitMenurutKode('KategoriKeluhan', 'Kode', 'KK-IT'));
        $this->assertSame($unit['TEKFAS'], $unitMenurutKode('KategoriKeluhan', 'Kode', 'KK-FAS'));
        $this->assertSame($unit['TEKFAS'], $unitMenurutKode('Gudang', 'Kode', 'GDG-01'), 'Gudang suku cadang utama');
    }

    /**
     * @param  list<StatusKeluhan>|list<StatusPerintahKerja>  $status
     * @return list<string>
     */
    private function statusFinal(array $status): array
    {
        return array_values(array_map(
            fn (StatusKeluhan|StatusPerintahKerja $satu): string => $satu->value,
            array_filter($status, fn (StatusKeluhan|StatusPerintahKerja $satu): bool => $satu->final()),
        ));
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
