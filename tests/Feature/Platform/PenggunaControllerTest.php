<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Izin;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Domain\Platform\Infrastructure\Persistence\Models\PeranIzin;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenggunaPeran;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PenggunaControllerTest extends TestCase
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
        $izin = Izin::firstOrCreate(['Kode' => 'Pengguna.Kelola'], ['Nama' => 'Kelola Pengguna', 'Modul' => 'IAM']);
        $peran = Peran::create(['Kode' => 'ADMIN', 'Nama' => 'Administrator']);
        PeranIzin::create(['PeranId' => $peran->Id, 'IzinId' => $izin->Id]);
        PenggunaPeran::create(['PenggunaId' => $admin->Id, 'PeranId' => $peran->Id]);
        $konteks->bersihkan();

        return $admin;
    }

    public function test_admin_dapat_membuat_pengguna_baru(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $admin = $this->buatAdmin($organisasi);

        $response = $this->actingAs($admin)->post('/platform/pengguna', [
            'Nama' => 'Teknisi Baru',
            'Email' => 'teknisi@amanpoll.test',
            'KataSandi' => 'kata-sandi-baru',
            'JenisPengguna' => 'Internal',
        ]);

        $response->assertSessionDoesntHaveErrors();
        $this->assertDatabaseHas('Pengguna', [
            'Email' => 'teknisi@amanpoll.test',
            'OrganisasiId' => $organisasi->Id,
            'Status' => 'Aktif',
        ]);
    }

    public function test_email_duplikat_dalam_satu_organisasi_ditolak(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $admin = $this->buatAdmin($organisasi);

        $response = $this->actingAs($admin)->post('/platform/pengguna', [
            'Nama' => 'Duplikat',
            'Email' => $admin->Email,
            'KataSandi' => 'kata-sandi-baru',
            'JenisPengguna' => 'Internal',
        ]);

        $response->assertSessionHasErrors('Email');
    }

    public function test_pengguna_tanpa_izin_tidak_dapat_melihat_daftar_pengguna(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $biasa = Pengguna::create([
            'OrganisasiId' => $organisasi->Id,
            'Nama' => 'Biasa',
            'Email' => 'biasa@amanpoll.test',
            'KataSandi' => 'rahasia',
            'Status' => 'Aktif',
        ]);

        $response = $this->actingAs($biasa)->get('/platform/pengguna');

        $response->assertForbidden();
    }

    public function test_pengguna_tanpa_izin_tidak_dapat_membuat_pengguna(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $biasa = Pengguna::create([
            'OrganisasiId' => $organisasi->Id,
            'Nama' => 'Biasa',
            'Email' => 'biasa@amanpoll.test',
            'KataSandi' => 'rahasia',
            'Status' => 'Aktif',
        ]);

        $response = $this->actingAs($biasa)->post('/platform/pengguna', [
            'Nama' => 'Lain',
            'Email' => 'lain@amanpoll.test',
            'KataSandi' => 'kata-sandi-baru',
            'JenisPengguna' => 'Internal',
        ]);

        $response->assertForbidden();
    }

    public function test_admin_dapat_menonaktifkan_pengguna_lain(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $admin = $this->buatAdmin($organisasi);
        $teknisi = Pengguna::create([
            'OrganisasiId' => $organisasi->Id,
            'Nama' => 'Teknisi',
            'Email' => 'teknisi@amanpoll.test',
            'KataSandi' => 'rahasia',
            'Status' => 'Aktif',
        ]);

        $response = $this->actingAs($admin)->put("/platform/pengguna/{$teknisi->Id}/status", [
            'Status' => 'Nonaktif',
        ]);

        $response->assertSessionDoesntHaveErrors();
        $this->assertDatabaseHas('Pengguna', ['Id' => $teknisi->Id, 'Status' => 'Nonaktif']);
    }

    public function test_admin_tidak_dapat_menonaktifkan_akun_sendiri(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $admin = $this->buatAdmin($organisasi);

        $response = $this->actingAs($admin)->put("/platform/pengguna/{$admin->Id}/status", [
            'Status' => 'Nonaktif',
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseHas('Pengguna', ['Id' => $admin->Id, 'Status' => 'Aktif']);
    }

    public function test_organisasi_a_tidak_dapat_mengubah_pengguna_organisasi_b(): void
    {
        $organisasiA = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $organisasiB = Organisasi::create(['Kode' => 'ORG-B', 'Nama' => 'Organisasi B']);
        $adminA = $this->buatAdmin($organisasiA);

        $penggunaB = Pengguna::create([
            'OrganisasiId' => $organisasiB->Id,
            'Nama' => 'Pengguna B',
            'Email' => 'b@amanpoll.test',
            'KataSandi' => 'rahasia',
            'Status' => 'Aktif',
        ]);

        $response = $this->actingAs($adminA)->put("/platform/pengguna/{$penggunaB->Id}", [
            'Nama' => 'Diretas',
            'Email' => 'diretas@amanpoll.test',
            'JenisPengguna' => 'Internal',
        ]);

        $response->assertStatus(404);
    }

    public function test_unit_organisasi_lintas_tenant_ditolak_saat_membuat_pengguna(): void
    {
        $organisasiA = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $organisasiB = Organisasi::create(['Kode' => 'ORG-B', 'Nama' => 'Organisasi B']);
        $admin = $this->buatAdmin($organisasiA);

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasiB->Id);
        $unitB = \App\Domain\Platform\Infrastructure\Persistence\Models\UnitOrganisasi::create(['Kode' => 'UNIT-B', 'Nama' => 'Unit B']);
        $konteks->bersihkan();

        $response = $this->actingAs($admin)->post('/platform/pengguna', [
            'Nama' => 'Coba Retas',
            'Email' => 'coba@amanpoll.test',
            'KataSandi' => 'kata-sandi-baru',
            'JenisPengguna' => 'Internal',
            'UnitOrganisasiId' => $unitB->Id,
        ]);

        $response->assertSessionHasErrors('UnitOrganisasiId');
    }
}
