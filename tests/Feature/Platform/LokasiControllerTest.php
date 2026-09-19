<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Izin;
use App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Domain\Platform\Infrastructure\Persistence\Models\PeranIzin;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenggunaPeran;
use App\Domain\Platform\Infrastructure\Persistence\Models\UnitOrganisasi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LokasiControllerTest extends TestCase
{
    use RefreshDatabase;

    private function buatAdmin(Organisasi $organisasi): Pengguna
    {
        $admin = Pengguna::create([
            'OrganisasiId' => $organisasi->Id,
            'Nama' => 'Admin',
            'Email' => 'admin+'.uniqid().'@amanpoll.test',
            'KataSandi' => 'rahasia',
            'Status' => 'Aktif',
        ]);

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        $izin = Izin::firstOrCreate(['Kode' => 'Pengaturan.Kelola'], ['Nama' => 'Kelola Pengaturan', 'Modul' => 'Sistem']);
        $peran = Peran::create(['Kode' => 'ADMIN', 'Nama' => 'Administrator']);
        PeranIzin::create(['PeranId' => $peran->Id, 'IzinId' => $izin->Id]);
        PenggunaPeran::create(['PenggunaId' => $admin->Id, 'PeranId' => $peran->Id]);
        $konteks->bersihkan();

        return $admin;
    }

    public function test_admin_dapat_membuat_lokasi_dan_menghubungkan_unit(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $admin = $this->buatAdmin($organisasi);

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        $unit = UnitOrganisasi::create(['Kode' => 'UNIT-1', 'Nama' => 'Unit Satu', 'Jenis' => 'Divisi']);
        $konteks->bersihkan();

        $response = $this->actingAs($admin)->post('/platform/lokasi', [
            'Kode' => 'LOK-1', 'Nama' => 'Lantai 1', 'Status' => 'Aktif', 'UnitOrganisasiId' => $unit->Id,
        ]);

        $response->assertSessionDoesntHaveErrors();
        $this->assertDatabaseHas('Lokasi', ['Kode' => 'LOK-1', 'UnitOrganisasiId' => $unit->Id, 'OrganisasiId' => $organisasi->Id]);
    }

    public function test_unit_organisasi_lintas_tenant_pada_lokasi_ditolak(): void
    {
        $organisasiA = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $organisasiB = Organisasi::create(['Kode' => 'ORG-B', 'Nama' => 'Organisasi B']);
        $admin = $this->buatAdmin($organisasiA);

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasiB->Id);
        $unitB = UnitOrganisasi::create(['Kode' => 'UNIT-B', 'Nama' => 'Unit B', 'Jenis' => 'Divisi']);
        $konteks->bersihkan();

        $response = $this->actingAs($admin)->post('/platform/lokasi', [
            'Kode' => 'LOK-1', 'Nama' => 'Lantai 1', 'Status' => 'Aktif', 'UnitOrganisasiId' => $unitB->Id,
        ]);

        $response->assertSessionHasErrors('UnitOrganisasiId');
    }

    public function test_index_mengembalikan_seluruh_lokasi_untuk_filter_di_client(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $admin = $this->buatAdmin($organisasi);

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        Lokasi::create(['Kode' => 'LOK-GUDANG', 'Nama' => 'Gudang Utama', 'Status' => 'Aktif']);
        Lokasi::create(['Kode' => 'LOK-KANTOR', 'Nama' => 'Kantor Pusat', 'Status' => 'Aktif']);
        $konteks->bersihkan();

        $response = $this->actingAs($admin)->get('/platform/lokasi');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->has('lokasi', 2));
    }

    public function test_hierarki_melingkar_pada_lokasi_ditolak(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $admin = $this->buatAdmin($organisasi);

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        $induk = Lokasi::create(['Kode' => 'INDUK', 'Nama' => 'Induk', 'Status' => 'Aktif']);
        $anak = Lokasi::create(['Kode' => 'ANAK', 'Nama' => 'Anak', 'Status' => 'Aktif', 'IndukId' => $induk->Id]);
        $konteks->bersihkan();

        $response = $this->actingAs($admin)->put("/platform/lokasi/{$induk->Id}", [
            'Kode' => 'INDUK', 'Nama' => 'Induk', 'Status' => 'Aktif', 'IndukId' => $anak->Id,
        ]);

        $response->assertStatus(422);
    }

    public function test_lokasi_dengan_sub_lokasi_tidak_dapat_dihapus(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $admin = $this->buatAdmin($organisasi);

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        $induk = Lokasi::create(['Kode' => 'INDUK', 'Nama' => 'Induk', 'Status' => 'Aktif']);
        Lokasi::create(['Kode' => 'ANAK', 'Nama' => 'Anak', 'Status' => 'Aktif', 'IndukId' => $induk->Id]);
        $konteks->bersihkan();

        $response = $this->actingAs($admin)->delete("/platform/lokasi/{$induk->Id}");

        $response->assertStatus(422);
    }

    public function test_organisasi_a_tidak_dapat_mengubah_lokasi_organisasi_b(): void
    {
        $organisasiA = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $organisasiB = Organisasi::create(['Kode' => 'ORG-B', 'Nama' => 'Organisasi B']);
        $adminA = $this->buatAdmin($organisasiA);

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasiB->Id);
        $lokasiB = Lokasi::create(['Kode' => 'LOK-B', 'Nama' => 'Lokasi B', 'Status' => 'Aktif']);
        $konteks->bersihkan();

        $response = $this->actingAs($adminA)->put("/platform/lokasi/{$lokasiB->Id}", [
            'Kode' => 'DIRETAS', 'Nama' => 'Diretas', 'Status' => 'Aktif',
        ]);

        $response->assertStatus(404);
    }
}
