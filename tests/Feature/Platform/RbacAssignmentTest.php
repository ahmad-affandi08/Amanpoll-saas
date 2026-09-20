<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Core\Izin\PemeriksaIzin;
use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Izin;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenggunaPeran;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Domain\Platform\Infrastructure\Persistence\Models\PeranIzin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RbacAssignmentTest extends TestCase
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
        $peranAdmin = Peran::create(['Kode' => 'ADMIN', 'Nama' => 'Administrator']);
        PeranIzin::create(['PeranId' => $peranAdmin->Id, 'IzinId' => $izin->Id]);
        PenggunaPeran::create(['PenggunaId' => $admin->Id, 'PeranId' => $peranAdmin->Id]);
        $konteks->bersihkan();

        return $admin;
    }

    public function test_admin_dapat_menetapkan_peran_ke_pengguna_lain(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $admin = $this->buatAdmin($organisasi);

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        $teknisi = Pengguna::create([
            'OrganisasiId' => $organisasi->Id,
            'Nama' => 'Teknisi',
            'Email' => 'teknisi@amanpoll.test',
            'KataSandi' => 'rahasia',
            'Status' => 'Aktif',
        ]);
        $peranTeknisi = Peran::create(['Kode' => 'TEKNISI', 'Nama' => 'Teknisi']);
        $konteks->bersihkan();

        $response = $this->actingAs($admin)->post("/platform/pengguna/{$teknisi->Id}/peran", [
            'PeranId' => $peranTeknisi->Id,
        ]);

        $response->assertSessionDoesntHaveErrors();
        $this->assertDatabaseHas('PenggunaPeran', ['PenggunaId' => $teknisi->Id, 'PeranId' => $peranTeknisi->Id]);
    }

    public function test_penetapan_peran_duplikat_ditolak(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $admin = $this->buatAdmin($organisasi);

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        $peranLain = Peran::create(['Kode' => 'LAIN', 'Nama' => 'Lain']);
        PenggunaPeran::create(['PenggunaId' => $admin->Id, 'PeranId' => $peranLain->Id]);
        $konteks->bersihkan();

        $response = $this->actingAs($admin)->post("/platform/pengguna/{$admin->Id}/peran", [
            'PeranId' => $peranLain->Id,
        ]);

        $response->assertStatus(409);
    }

    public function test_tidak_dapat_menetapkan_peran_organisasi_lain(): void
    {
        $organisasiA = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $organisasiB = Organisasi::create(['Kode' => 'ORG-B', 'Nama' => 'Organisasi B']);
        $admin = $this->buatAdmin($organisasiA);

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasiB->Id);
        $peranB = Peran::create(['Kode' => 'PERAN-B', 'Nama' => 'Peran B']);
        $konteks->bersihkan();

        $response = $this->actingAs($admin)->post("/platform/pengguna/{$admin->Id}/peran", [
            'PeranId' => $peranB->Id,
        ]);

        $response->assertStatus(404);
    }

    public function test_tidak_dapat_menetapkan_peran_ke_pengguna_organisasi_lain(): void
    {
        $organisasiA = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $organisasiB = Organisasi::create(['Kode' => 'ORG-B', 'Nama' => 'Organisasi B']);
        $admin = $this->buatAdmin($organisasiA);

        $penggunaB = Pengguna::create([
            'OrganisasiId' => $organisasiB->Id,
            'Nama' => 'Pengguna B',
            'Email' => 'b@amanpoll.test',
            'KataSandi' => 'rahasia',
            'Status' => 'Aktif',
        ]);

        $response = $this->actingAs($admin)->post("/platform/pengguna/{$penggunaB->Id}/peran", [
            'PeranId' => 'apapun',
        ]);

        $response->assertStatus(404);
    }

    public function test_mencabut_peran_dari_pengguna(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $admin = $this->buatAdmin($organisasi);

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        $peranLain = Peran::create(['Kode' => 'LAIN', 'Nama' => 'Lain']);
        $penugasan = PenggunaPeran::create(['PenggunaId' => $admin->Id, 'PeranId' => $peranLain->Id]);
        $konteks->bersihkan();

        $response = $this->actingAs($admin)->delete("/platform/pengguna-peran/{$penugasan->Id}");

        $response->assertSessionDoesntHaveErrors();
        $this->assertDatabaseMissing('PenggunaPeran', ['Id' => $penugasan->Id]);
    }

    public function test_sinkronkan_izin_peran_langsung_memengaruhi_akses(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $admin = $this->buatAdmin($organisasi);

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        $izinBaru = Izin::create(['Kode' => 'Aset.Lihat', 'Nama' => 'Lihat Aset', 'Modul' => 'Aset']);
        $peranAdmin = Peran::where('Kode', 'ADMIN')->first();
        $izinAdmin = Izin::where('Kode', 'Pengguna.Kelola')->first();
        $konteks->bersihkan();

        $response = $this->actingAs($admin)->put("/platform/peran/{$peranAdmin->Id}/izin", [
            'IzinId' => [$izinBaru->Id, $izinAdmin->Id],
        ]);

        $response->assertSessionDoesntHaveErrors();
        $this->assertDatabaseHas('PeranIzin', ['PeranId' => $peranAdmin->Id, 'IzinId' => $izinBaru->Id]);

        $konteks->tetapkan($organisasi->Id);
        $pemeriksaIzin = app(PemeriksaIzin::class);
        $this->assertTrue($pemeriksaIzin->boleh($admin->Id, 'Aset.Lihat'));
        $konteks->bersihkan();
    }

    public function test_pengguna_tanpa_izin_tidak_dapat_menetapkan_peran(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $biasa = Pengguna::create([
            'OrganisasiId' => $organisasi->Id,
            'Nama' => 'Biasa',
            'Email' => 'biasa@amanpoll.test',
            'KataSandi' => 'rahasia',
            'Status' => 'Aktif',
        ]);

        $response = $this->actingAs($biasa)->post("/platform/pengguna/{$biasa->Id}/peran", [
            'PeranId' => 'apapun',
        ]);

        $response->assertForbidden();
    }
}
