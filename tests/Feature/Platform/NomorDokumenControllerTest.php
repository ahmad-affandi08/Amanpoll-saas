<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Platform\Application\Services\LayananNomorDokumen;
use App\Domain\Platform\Infrastructure\Persistence\Models\Izin;
use App\Domain\Platform\Infrastructure\Persistence\Models\NomorDokumen;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Domain\Platform\Infrastructure\Persistence\Models\PeranIzin;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenggunaPeran;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NomorDokumenControllerTest extends TestCase
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

    public function test_admin_dapat_membuat_pola_nomor_dokumen(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $admin = $this->buatAdmin($organisasi);

        $response = $this->actingAs($admin)->post('/platform/nomor-dokumen', [
            'JenisDokumen' => 'PerintahKerja',
            'Awalan' => 'PK',
            'FormatNomor' => '{Awalan}/{Nomor:4}/{Tahun}',
            'ResetPeriode' => 'Tahunan',
        ]);

        $response->assertSessionDoesntHaveErrors();
        $this->assertDatabaseHas('NomorDokumen', ['JenisDokumen' => 'PerintahKerja', 'OrganisasiId' => $organisasi->Id, 'NomorTerakhir' => 0]);
    }

    public function test_format_nomor_tanpa_placeholder_nomor_ditolak(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $admin = $this->buatAdmin($organisasi);

        $response = $this->actingAs($admin)->post('/platform/nomor-dokumen', [
            'JenisDokumen' => 'PerintahKerja',
            'FormatNomor' => '{Awalan}/{Tahun}',
            'ResetPeriode' => 'Tahunan',
        ]);

        $response->assertSessionHasErrors('FormatNomor');
    }

    public function test_jenis_dokumen_duplikat_dalam_organisasi_ditolak(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $admin = $this->buatAdmin($organisasi);

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        NomorDokumen::create(['JenisDokumen' => 'PerintahKerja', 'FormatNomor' => '{Nomor:4}', 'ResetPeriode' => 'Tahunan']);
        $konteks->bersihkan();

        $response = $this->actingAs($admin)->post('/platform/nomor-dokumen', [
            'JenisDokumen' => 'PerintahKerja',
            'FormatNomor' => '{Nomor:4}',
            'ResetPeriode' => 'Tahunan',
        ]);

        $response->assertSessionHasErrors('JenisDokumen');
    }

    public function test_pratinjau_tidak_mengubah_nomor_terakhir(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $this->buatAdmin($organisasi);

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        NomorDokumen::create(['JenisDokumen' => 'PerintahKerja', 'Awalan' => 'PK', 'FormatNomor' => '{Awalan}-{Nomor:3}', 'ResetPeriode' => 'TidakAda']);

        $layanan = app(LayananNomorDokumen::class);
        $pratinjau1 = $layanan->pratinjau($organisasi->Id, 'PerintahKerja');
        $pratinjau2 = $layanan->pratinjau($organisasi->Id, 'PerintahKerja');
        $konteks->bersihkan();

        $this->assertSame('PK-001', $pratinjau1);
        $this->assertSame($pratinjau1, $pratinjau2);
    }

    public function test_berikutnya_meningkat_setiap_dipanggil_dan_mengunci_periode(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $this->buatAdmin($organisasi);

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        NomorDokumen::create(['JenisDokumen' => 'PerintahKerja', 'Awalan' => 'PK', 'FormatNomor' => '{Awalan}-{Nomor:3}', 'ResetPeriode' => 'TidakAda']);

        $layanan = app(LayananNomorDokumen::class);
        $nomor1 = $layanan->berikutnya($organisasi->Id, 'PerintahKerja');
        $nomor2 = $layanan->berikutnya($organisasi->Id, 'PerintahKerja');
        $konteks->bersihkan();

        $this->assertSame('PK-001', $nomor1);
        $this->assertSame('PK-002', $nomor2);
    }

    public function test_reset_periode_tahunan_mengembalikan_nomor_ke_satu(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $this->buatAdmin($organisasi);

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        $pola = NomorDokumen::create(['JenisDokumen' => 'PerintahKerja', 'Awalan' => 'PK', 'FormatNomor' => '{Awalan}-{Nomor:3}-{Tahun}', 'ResetPeriode' => 'Tahunan']);
        // Simulasikan periode tahun lalu yang sudah pernah dipakai.
        $pola->forceFill(['NomorTerakhir' => 50, 'PeriodeAktif' => (string) (now()->year - 1)])->save();

        $nomor = app(LayananNomorDokumen::class)->berikutnya($organisasi->Id, 'PerintahKerja');
        $konteks->bersihkan();

        $this->assertSame('PK-001-'.now()->format('Y'), $nomor);
    }

    public function test_pengguna_tanpa_izin_tidak_dapat_membuat_pola_nomor_dokumen(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $biasa = Pengguna::create([
            'OrganisasiId' => $organisasi->Id,
            'Nama' => 'Biasa',
            'Email' => 'biasa@amanpoll.test',
            'KataSandi' => 'rahasia',
            'Status' => 'Aktif',
        ]);

        $response = $this->actingAs($biasa)->post('/platform/nomor-dokumen', [
            'JenisDokumen' => 'PerintahKerja',
            'FormatNomor' => '{Nomor:4}',
            'ResetPeriode' => 'Tahunan',
        ]);

        $response->assertForbidden();
    }

    public function test_pola_yang_sudah_dipakai_tidak_dapat_dihapus(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $admin = $this->buatAdmin($organisasi);

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        $pola = NomorDokumen::create(['JenisDokumen' => 'PerintahKerja', 'FormatNomor' => '{Nomor:4}', 'ResetPeriode' => 'TidakAda']);
        app(LayananNomorDokumen::class)->berikutnya($organisasi->Id, 'PerintahKerja');
        $konteks->bersihkan();

        $response = $this->actingAs($admin)->delete("/platform/nomor-dokumen/{$pola->Id}");

        $response->assertStatus(422);
    }
}
