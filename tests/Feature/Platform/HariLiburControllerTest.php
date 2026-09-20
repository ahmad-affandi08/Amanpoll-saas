<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\HariLibur;
use App\Domain\Platform\Infrastructure\Persistence\Models\Izin;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenggunaPeran;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Domain\Platform\Infrastructure\Persistence\Models\PeranIzin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HariLiburControllerTest extends TestCase
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

    public function test_admin_dapat_menambah_hari_libur(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $admin = $this->buatAdmin($organisasi);

        $response = $this->actingAs($admin)->post('/platform/hari-libur', [
            'Tanggal' => '2026-12-25', 'Nama' => 'Natal', 'BerulangTahunan' => true,
        ]);

        $response->assertSessionDoesntHaveErrors();
        $this->assertDatabaseHas('HariLibur', ['Nama' => 'Natal', 'OrganisasiId' => $organisasi->Id]);
    }

    public function test_hari_libur_duplikat_ditolak(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $admin = $this->buatAdmin($organisasi);

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        HariLibur::create(['Tanggal' => '2026-12-25', 'Nama' => 'Natal', 'BerulangTahunan' => true]);
        $konteks->bersihkan();

        $response = $this->actingAs($admin)->post('/platform/hari-libur', [
            'Tanggal' => '2026-12-25', 'Nama' => 'Natal', 'BerulangTahunan' => true,
        ]);

        $response->assertSessionHasErrors('Tanggal');
    }

    public function test_admin_dapat_menghapus_hari_libur(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $admin = $this->buatAdmin($organisasi);

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        $libur = HariLibur::create(['Tanggal' => '2026-08-17', 'Nama' => 'Kemerdekaan', 'BerulangTahunan' => true]);
        $konteks->bersihkan();

        $response = $this->actingAs($admin)->delete("/platform/hari-libur/{$libur->Id}");

        $response->assertSessionDoesntHaveErrors();
        $this->assertDatabaseMissing('HariLibur', ['Id' => $libur->Id]);
    }

    public function test_pengguna_tanpa_izin_tidak_dapat_menambah_hari_libur(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $biasa = Pengguna::create([
            'OrganisasiId' => $organisasi->Id,
            'Nama' => 'Biasa',
            'Email' => 'biasa@amanpoll.test',
            'KataSandi' => 'rahasia',
            'Status' => 'Aktif',
        ]);

        $response = $this->actingAs($biasa)->post('/platform/hari-libur', [
            'Tanggal' => '2026-12-25', 'Nama' => 'Natal', 'BerulangTahunan' => true,
        ]);

        $response->assertForbidden();
    }

    public function test_organisasi_a_tidak_dapat_menghapus_hari_libur_organisasi_b(): void
    {
        $organisasiA = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $organisasiB = Organisasi::create(['Kode' => 'ORG-B', 'Nama' => 'Organisasi B']);
        $adminA = $this->buatAdmin($organisasiA);

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasiB->Id);
        $liburB = HariLibur::create(['Tanggal' => '2026-08-17', 'Nama' => 'Kemerdekaan', 'BerulangTahunan' => true]);
        $konteks->bersihkan();

        $response = $this->actingAs($adminA)->delete("/platform/hari-libur/{$liburB->Id}");

        $response->assertStatus(404);
    }
}
