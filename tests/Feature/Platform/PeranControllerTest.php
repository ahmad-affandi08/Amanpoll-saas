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

class PeranControllerTest extends TestCase
{
    use RefreshDatabase;

    private function buatPenggunaDenganIzin(Organisasi $organisasi, string $kodeIzin): Pengguna
    {
        $pengguna = Pengguna::create([
            'OrganisasiId' => $organisasi->Id,
            'Nama' => 'Admin',
            'Email' => 'admin+'.uniqid().'@amanpoll.test',
            'KataSandi' => 'rahasia',
            'Status' => 'Aktif',
        ]);

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);

        $izin = Izin::firstOrCreate(['Kode' => $kodeIzin], ['Nama' => $kodeIzin, 'Modul' => 'IAM']);
        $peran = Peran::create(['Kode' => 'ADMIN', 'Nama' => 'Administrator']);
        PeranIzin::create(['PeranId' => $peran->Id, 'IzinId' => $izin->Id]);
        PenggunaPeran::create(['PenggunaId' => $pengguna->Id, 'PeranId' => $peran->Id]);

        $konteks->bersihkan();

        return $pengguna;
    }

    public function test_pengguna_dengan_izin_dapat_membuat_peran(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $admin = $this->buatPenggunaDenganIzin($organisasi, 'Pengguna.Kelola');

        $response = $this->actingAs($admin)->post('/platform/peran', [
            'Kode' => 'TEKNISI',
            'Nama' => 'Teknisi',
        ]);

        $response->assertSessionDoesntHaveErrors();
        $this->assertDatabaseHas('Peran', ['Kode' => 'TEKNISI', 'OrganisasiId' => $organisasi->Id]);
    }

    public function test_pengguna_tanpa_izin_ditolak_membuat_peran(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $pengguna = Pengguna::create([
            'OrganisasiId' => $organisasi->Id,
            'Nama' => 'Biasa',
            'Email' => 'biasa@amanpoll.test',
            'KataSandi' => 'rahasia',
            'Status' => 'Aktif',
        ]);

        $response = $this->actingAs($pengguna)->post('/platform/peran', [
            'Kode' => 'TEKNISI',
            'Nama' => 'Teknisi',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('Peran', ['Kode' => 'TEKNISI']);
    }

    public function test_kode_peran_duplikat_dalam_satu_organisasi_ditolak(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $admin = $this->buatPenggunaDenganIzin($organisasi, 'Pengguna.Kelola');

        $response = $this->actingAs($admin)->post('/platform/peran', [
            'Kode' => 'ADMIN',
            'Nama' => 'Administrator Duplikat',
        ]);

        $response->assertSessionHasErrors('Kode');
    }

    public function test_peran_bawaan_sistem_tidak_dapat_dihapus(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $admin = $this->buatPenggunaDenganIzin($organisasi, 'Pengguna.Kelola');

        app(KonteksOrganisasi::class)->tetapkan($organisasi->Id);
        $peranSistem = Peran::create(['Kode' => 'SUPER', 'Nama' => 'Super Admin', 'BawaanSistem' => true]);
        app(KonteksOrganisasi::class)->bersihkan();

        $response = $this->actingAs($admin)->delete("/platform/peran/{$peranSistem->Id}");

        $response->assertStatus(422);
        $this->assertDatabaseHas('Peran', ['Id' => $peranSistem->Id]);
    }

    public function test_organisasi_a_tidak_dapat_mengubah_peran_organisasi_b(): void
    {
        $organisasiA = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $organisasiB = Organisasi::create(['Kode' => 'ORG-B', 'Nama' => 'Organisasi B']);
        $adminA = $this->buatPenggunaDenganIzin($organisasiA, 'Pengguna.Kelola');

        app(KonteksOrganisasi::class)->tetapkan($organisasiB->Id);
        $peranB = Peran::create(['Kode' => 'RAHASIA', 'Nama' => 'Rahasia B']);
        app(KonteksOrganisasi::class)->bersihkan();

        $response = $this->actingAs($adminA)->put("/platform/peran/{$peranB->Id}", [
            'Kode' => 'DIRETAS',
            'Nama' => 'Diretas',
        ]);

        $response->assertStatus(404);
        $this->assertDatabaseHas('Peran', ['Id' => $peranB->Id, 'Kode' => 'RAHASIA']);
    }
}
