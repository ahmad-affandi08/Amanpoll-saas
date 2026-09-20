<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Izin;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenggunaPeran;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Domain\Platform\Infrastructure\Persistence\Models\PeranIzin;
use App\Domain\Platform\Infrastructure\Persistence\Models\UnitOrganisasi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnitOrganisasiControllerTest extends TestCase
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

    public function test_admin_dapat_membuat_unit_organisasi(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $admin = $this->buatAdmin($organisasi);

        $response = $this->actingAs($admin)->post('/platform/unit-organisasi', [
            'Kode' => 'UNIT-1', 'Nama' => 'Unit Satu', 'Jenis' => 'Divisi', 'Status' => 'Aktif',
        ]);

        $response->assertSessionDoesntHaveErrors();
        $this->assertDatabaseHas('UnitOrganisasi', ['Kode' => 'UNIT-1', 'OrganisasiId' => $organisasi->Id]);
    }

    public function test_kode_unit_duplikat_dalam_satu_organisasi_ditolak(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $admin = $this->buatAdmin($organisasi);

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        UnitOrganisasi::create(['Kode' => 'UNIT-1', 'Nama' => 'Unit Satu', 'Jenis' => 'Divisi']);
        $konteks->bersihkan();

        $response = $this->actingAs($admin)->post('/platform/unit-organisasi', [
            'Kode' => 'UNIT-1', 'Nama' => 'Unit Duplikat', 'Jenis' => 'Divisi', 'Status' => 'Aktif',
        ]);

        $response->assertSessionHasErrors('Kode');
    }

    public function test_menetapkan_induk_ke_diri_sendiri_ditolak(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $admin = $this->buatAdmin($organisasi);

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        $unit = UnitOrganisasi::create(['Kode' => 'UNIT-1', 'Nama' => 'Unit Satu', 'Jenis' => 'Divisi']);
        $konteks->bersihkan();

        $response = $this->actingAs($admin)->put("/platform/unit-organisasi/{$unit->Id}", [
            'Kode' => 'UNIT-1', 'Nama' => 'Unit Satu', 'Jenis' => 'Divisi', 'Status' => 'Aktif', 'IndukId' => $unit->Id,
        ]);

        $response->assertStatus(422);
    }

    public function test_hierarki_melingkar_ditolak(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $admin = $this->buatAdmin($organisasi);

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        $induk = UnitOrganisasi::create(['Kode' => 'INDUK', 'Nama' => 'Induk', 'Jenis' => 'Divisi']);
        $anak = UnitOrganisasi::create(['Kode' => 'ANAK', 'Nama' => 'Anak', 'Jenis' => 'Divisi', 'IndukId' => $induk->Id]);
        $konteks->bersihkan();

        // Coba jadikan induk sebagai anak dari anaknya sendiri -> melingkar.
        $response = $this->actingAs($admin)->put("/platform/unit-organisasi/{$induk->Id}", [
            'Kode' => 'INDUK', 'Nama' => 'Induk', 'Jenis' => 'Divisi', 'Status' => 'Aktif', 'IndukId' => $anak->Id,
        ]);

        $response->assertStatus(422);
    }

    public function test_unit_dengan_sub_unit_tidak_dapat_dihapus(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $admin = $this->buatAdmin($organisasi);

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        $induk = UnitOrganisasi::create(['Kode' => 'INDUK', 'Nama' => 'Induk', 'Jenis' => 'Divisi']);
        UnitOrganisasi::create(['Kode' => 'ANAK', 'Nama' => 'Anak', 'Jenis' => 'Divisi', 'IndukId' => $induk->Id]);
        $konteks->bersihkan();

        $response = $this->actingAs($admin)->delete("/platform/unit-organisasi/{$induk->Id}");

        $response->assertStatus(422);
        $this->assertDatabaseHas('UnitOrganisasi', ['Id' => $induk->Id, 'DihapusPada' => null]);
    }

    public function test_filter_status_aktif_hanya_menampilkan_unit_aktif(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $admin = $this->buatAdmin($organisasi);

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        UnitOrganisasi::create(['Kode' => 'UNIT-AKTIF', 'Nama' => 'Aktif', 'Jenis' => 'Divisi', 'Status' => 'Aktif']);
        UnitOrganisasi::create(['Kode' => 'UNIT-NONAKTIF', 'Nama' => 'Nonaktif', 'Jenis' => 'Divisi', 'Status' => 'Nonaktif']);
        $konteks->bersihkan();

        $response = $this->actingAs($admin)->get('/platform/unit-organisasi?status=Aktif');

        $response->assertOk();
    }

    public function test_organisasi_a_tidak_dapat_mengubah_unit_organisasi_b(): void
    {
        $organisasiA = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $organisasiB = Organisasi::create(['Kode' => 'ORG-B', 'Nama' => 'Organisasi B']);
        $adminA = $this->buatAdmin($organisasiA);

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasiB->Id);
        $unitB = UnitOrganisasi::create(['Kode' => 'UNIT-B', 'Nama' => 'Unit B', 'Jenis' => 'Divisi']);
        $konteks->bersihkan();

        $response = $this->actingAs($adminA)->put("/platform/unit-organisasi/{$unitB->Id}", [
            'Kode' => 'DIRETAS', 'Nama' => 'Diretas', 'Jenis' => 'Divisi', 'Status' => 'Aktif',
        ]);

        $response->assertStatus(404);
    }

    public function test_pengguna_tanpa_izin_tidak_dapat_membuat_unit_organisasi(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $biasa = Pengguna::create([
            'OrganisasiId' => $organisasi->Id,
            'Nama' => 'Biasa',
            'Email' => 'biasa@amanpoll.test',
            'KataSandi' => 'rahasia',
            'Status' => 'Aktif',
        ]);

        $response = $this->actingAs($biasa)->post('/platform/unit-organisasi', [
            'Kode' => 'UNIT-1', 'Nama' => 'Unit Satu', 'Jenis' => 'Divisi', 'Status' => 'Aktif',
        ]);

        $response->assertForbidden();
    }
}
