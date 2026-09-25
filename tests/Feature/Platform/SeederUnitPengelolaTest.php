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
use Database\Seeders\DemoFondasiSeeder;
use Database\Seeders\DemoMasterSeeder;
use Database\Seeders\FiturPaketSeeder;
use Database\Seeders\IzinSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Data contoh dua unit pengelola (TASK 40.06) di perusahaan demo PT Sinar Nusantara
 * Industri: bagian Teknik & Fasilitas dan IT memegang aset, gudang, serta koordinator
 * dan teknisi yang perannya berlingkup unit masing-masing.
 *
 * Test struktur memakai fondasi dan master saja (cepat dan idempoten); satu test
 * menjalankan seluruh DatabaseSeeder untuk memastikan riwayat transaksi pun tidak
 * ada yang jatuh ke antrian tanpa unit pengelola.
 */
class SeederUnitPengelolaTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        app(KonteksOrganisasi::class)->bersihkan();

        parent::tearDown();
    }

    private function semaiFondasiDanMaster(): void
    {
        $this->seed([IzinSeeder::class, FiturPaketSeeder::class, DemoFondasiSeeder::class, DemoMasterSeeder::class]);
    }

    private function organisasiId(): string
    {
        return (string) DB::table('Organisasi')->where('Kode', 'SNI')->value('Id');
    }

    public function test_fondasi_dan_master_menyemai_dua_unit_pengelola_dan_aman_diulang(): void
    {
        $this->semaiFondasiDanMaster();
        $jumlahAsetPertama = DB::table('Aset')->count();
        $this->semaiFondasiDanMaster();

        $unit = DB::table('UnitOrganisasi')->where('OrganisasiId', $this->organisasiId())->where('MengelolaAset', true)->pluck('Id', 'Kode');

        $this->assertSame(['IT', 'TEKFAS'], $unit->keys()->sort()->values()->all());
        $this->assertGreaterThan(0, $jumlahAsetPertama);
        $this->assertSame($jumlahAsetPertama, DB::table('Aset')->count(), 'Menjalankan ulang tidak menggandakan aset');
        $this->assertSame(1, DB::table('Pengguna')->where('Email', 'koordinator.it@amanpoll.test')->count());

        foreach ($unit as $kode => $unitId) {
            $this->assertGreaterThan(0, DB::table('Aset')->where('UnitPengelolaId', $unitId)->count(), "Aset {$kode}");
            $this->assertGreaterThan(0, DB::table('Gudang')->where('UnitPengelolaId', $unitId)->count(), "Gudang {$kode}");
            // Koordinator dan teknisi berlingkup unitnya sendiri.
            $this->assertGreaterThanOrEqual(2, DB::table('PenggunaPeran')->where('UnitOrganisasiId', $unitId)->count(), "Peran berlingkup {$kode}");
        }
    }

    public function test_koordinator_it_contoh_berlingkup_dan_hanya_melihat_gudang_it(): void
    {
        $this->semaiFondasiDanMaster();

        $koordinator = Pengguna::query()->where('Email', 'koordinator.it@amanpoll.test')->firstOrFail();
        app(KonteksOrganisasi::class)->tetapkan((string) $koordinator->OrganisasiId);

        $this->assertFalse(app(LingkupAkses::class)->tanpaBatas((string) $koordinator->Id));

        $this->actingAs($koordinator, 'web');
        $this->assertSame(['Gudang Kecil Menara Sinar'], Gudang::query()->pluck('Nama')->all());
    }

    /**
     * Tidak ada data contoh yang menunjuk unit tak bertanda atau jatuh tanpa
     * unit pengelola. Keluhan dan perintah kerja hanya dihitung yang belum
     * final dan belum dihapus: riwayat tiket final boleh menyebut unit lama.
     */
    public function test_seluruh_data_contoh_menunjuk_unit_pengelola_bertanda(): void
    {
        $this->seed(DatabaseSeeder::class);

        $organisasiId = $this->organisasiId();
        $unit = DB::table('UnitOrganisasi')->where('OrganisasiId', $organisasiId)->where('MengelolaAset', true)->pluck('Id', 'Kode');

        foreach (['Aset', 'KategoriKeluhan', 'Gudang', 'RencanaPemeliharaan', 'RencanaKalibrasi', 'Keluhan', 'PerintahKerja'] as $tabel) {
            // Pembanding: tabel yang dijaga memang berisi, jadi nol di bawah bukan karena kosong.
            $this->assertGreaterThan(0, DB::table($tabel)->where('OrganisasiId', $organisasiId)->count(), "Baris contoh {$tabel}");

            $tanpaUnitBertanda = DB::table($tabel)
                ->where('OrganisasiId', $organisasiId)
                ->when(in_array($tabel, ['Aset', 'Keluhan', 'PerintahKerja'], true), fn ($kueri) => $kueri->whereNull('DihapusPada'))
                ->when($tabel === 'Keluhan', fn ($kueri) => $kueri->whereNotIn('Status', $this->statusFinal(StatusKeluhan::cases())))
                ->when($tabel === 'PerintahKerja', fn ($kueri) => $kueri->whereNotIn('Status', $this->statusFinal(StatusPerintahKerja::cases())))
                ->where(fn ($kueri) => $kueri->whereNull('UnitPengelolaId')->orWhereNotIn('UnitPengelolaId', $unit->values()->all()))
                ->count();

            $this->assertSame(0, $tanpaUnitBertanda, "{$tabel} tanpa unit pengelola bertanda");
        }

        $unitAset = fn (string $kode): mixed => DB::table('Aset')->where('OrganisasiId', $organisasiId)->where('KodeAset', $kode)->value('UnitPengelolaId');

        $this->assertSame($unit['IT'], $unitAset('IT-SRV-01'), 'Server dipelihara IT');
        $this->assertSame($unit['TEKFAS'], $unitAset('UTL-GEN-01'), 'Genset dipelihara Teknik & Fasilitas');
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
}
