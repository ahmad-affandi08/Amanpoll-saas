<?php

declare(strict_types=1);

namespace Tests\Feature\Shared;

use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use Database\Seeders\IzinSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Memastikan migration hasil konversi Amanpoll_Database_MySQL.sql benar-benar
 * bisa dipakai model Eloquent yang sudah ada, bukan hanya berhasil dieksekusi.
 */
class MigrasiSchemaDomainTest extends TestCase
{
    use RefreshDatabase;

    public function test_seluruh_tabel_domain_dan_view_operasional_terbentuk(): void
    {
        $this->assertTrue(Schema::hasTable('Aset'));
        $this->assertTrue(Schema::hasTable('PerintahKerja'));
        $this->assertTrue(Schema::hasTable('Izin'));
    }

    public function test_izin_dasar_dapat_di_seed(): void
    {
        $this->seed(IzinSeeder::class);

        $this->assertSame(20, DB::table('Izin')->count());
        $this->assertDatabaseHas('Izin', ['Kode' => 'Anggaran.Sesuaikan', 'Modul' => 'Pengadaan']);
    }

    public function test_model_organisasi_dan_pengguna_bekerja_di_atas_migration(): void
    {
        $organisasi = Organisasi::create([
            'Kode' => 'AMN-001',
            'Nama' => 'Amanpoll Demo',
            'ZonaWaktu' => 'Asia/Jakarta',
            'Status' => 'Aktif',
        ]);

        $this->assertNotEmpty($organisasi->Id);

        $pengguna = Pengguna::create([
            'OrganisasiId' => $organisasi->Id,
            'Nama' => 'Admin Demo',
            'Email' => 'admin@amanpoll.test',
            'KataSandi' => 'rahasia-diacak',
            'Status' => 'Aktif',
        ]);

        $this->assertSame($organisasi->Id, $pengguna->OrganisasiId);
        $this->assertDatabaseHas('Pengguna', [
            'Id' => $pengguna->Id,
            'Email' => 'admin@amanpoll.test',
        ]);
    }
}
