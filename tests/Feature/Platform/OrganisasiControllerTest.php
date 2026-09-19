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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OrganisasiControllerTest extends TestCase
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

    public function test_admin_dapat_mengubah_profil_organisasi(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A', 'ZonaWaktu' => 'Asia/Jakarta']);
        $admin = $this->buatAdmin($organisasi);

        $response = $this->actingAs($admin)->put('/platform/organisasi', [
            'Nama' => 'Organisasi A Baru',
            'ZonaWaktu' => 'Asia/Makassar',
        ]);

        $response->assertSessionDoesntHaveErrors();
        $this->assertDatabaseHas('Organisasi', [
            'Id' => $organisasi->Id,
            'Nama' => 'Organisasi A Baru',
            'ZonaWaktu' => 'Asia/Makassar',
        ]);
    }

    public function test_zona_waktu_tidak_valid_ditolak(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A', 'ZonaWaktu' => 'Asia/Jakarta']);
        $admin = $this->buatAdmin($organisasi);

        $response = $this->actingAs($admin)->put('/platform/organisasi', [
            'Nama' => 'Organisasi A',
            'ZonaWaktu' => 'Zona/TidakAda',
        ]);

        $response->assertSessionHasErrors('ZonaWaktu');
    }

    public function test_status_dan_kode_tidak_bisa_diubah_lewat_endpoint_profil(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A', 'ZonaWaktu' => 'Asia/Jakarta', 'Status' => 'Aktif']);
        $admin = $this->buatAdmin($organisasi);

        $this->actingAs($admin)->put('/platform/organisasi', [
            'Nama' => 'Organisasi A',
            'ZonaWaktu' => 'Asia/Jakarta',
            'Status' => 'Nonaktif',
            'Kode' => 'ORG-DIRETAS',
        ]);

        $this->assertDatabaseHas('Organisasi', ['Id' => $organisasi->Id, 'Status' => 'Aktif', 'Kode' => 'ORG-A']);
    }

    public function test_pengguna_tanpa_izin_tidak_dapat_mengubah_profil_organisasi(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A', 'ZonaWaktu' => 'Asia/Jakarta']);
        $biasa = Pengguna::create([
            'OrganisasiId' => $organisasi->Id,
            'Nama' => 'Biasa',
            'Email' => 'biasa@amanpoll.test',
            'KataSandi' => 'rahasia',
            'Status' => 'Aktif',
        ]);

        $response = $this->actingAs($biasa)->put('/platform/organisasi', [
            'Nama' => 'Diretas',
            'ZonaWaktu' => 'Asia/Jakarta',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('Organisasi', ['Id' => $organisasi->Id, 'Nama' => 'Organisasi A']);
    }

    public function test_admin_dapat_mengunggah_logo_organisasi(): void
    {
        Storage::fake('public');
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A', 'ZonaWaktu' => 'Asia/Jakarta']);
        $admin = $this->buatAdmin($organisasi);

        $response = $this->actingAs($admin)->post('/platform/organisasi/logo', [
            'Logo' => UploadedFile::fake()->image('logo.png'),
        ]);

        $response->assertSessionDoesntHaveErrors();
        $organisasi->refresh();
        $this->assertNotNull($organisasi->LogoUrl);
    }

    public function test_mengubah_organisasi_a_tidak_memengaruhi_organisasi_b(): void
    {
        $organisasiA = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A', 'ZonaWaktu' => 'Asia/Jakarta']);
        $organisasiB = Organisasi::create(['Kode' => 'ORG-B', 'Nama' => 'Organisasi B', 'ZonaWaktu' => 'Asia/Jakarta']);
        $adminA = $this->buatAdmin($organisasiA);

        $this->actingAs($adminA)->put('/platform/organisasi', [
            'Nama' => 'Organisasi A Baru',
            'ZonaWaktu' => 'Asia/Jakarta',
        ]);

        $this->assertDatabaseHas('Organisasi', ['Id' => $organisasiB->Id, 'Nama' => 'Organisasi B']);
    }
}
