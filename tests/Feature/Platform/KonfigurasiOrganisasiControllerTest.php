<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Core\Konfigurasi\LayananKonfigurasi;
use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Izin;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenggunaPeran;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Domain\Platform\Infrastructure\Persistence\Models\PeranIzin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class KonfigurasiOrganisasiControllerTest extends TestCase
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

    public function test_konfigurasi_belum_diubah_mengembalikan_nilai_default(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $admin = $this->buatAdmin($organisasi);

        $response = $this->actingAs($admin)->get('/platform/konfigurasi');

        $response->assertOk();
    }

    public function test_admin_dapat_mengubah_nilai_konfigurasi(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $admin = $this->buatAdmin($organisasi);

        $response = $this->actingAs($admin)->put('/platform/konfigurasi/Notifikasi.PengingatHariSebelum', [
            'Nilai' => 7,
        ]);

        $response->assertSessionDoesntHaveErrors();

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        $nilai = app(LayananKonfigurasi::class)->ambil($organisasi->Id, 'Notifikasi.PengingatHariSebelum');
        $konteks->bersihkan();

        $this->assertSame(7, $nilai);
    }

    public function test_kunci_konfigurasi_tidak_dikenal_ditolak(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $admin = $this->buatAdmin($organisasi);

        $response = $this->actingAs($admin)->put('/platform/konfigurasi/Tidak.Dikenal', [
            'Nilai' => 'apapun',
        ]);

        $response->assertStatus(404);
    }

    public function test_tipe_nilai_yang_salah_ditolak(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $admin = $this->buatAdmin($organisasi);

        $response = $this->actingAs($admin)->put('/platform/konfigurasi/Notifikasi.PengingatHariSebelum', [
            'Nilai' => 'bukan-angka',
        ]);

        $response->assertSessionHasErrors('Nilai');
    }

    public function test_cache_konfigurasi_langsung_bersih_setelah_diubah(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $admin = $this->buatAdmin($organisasi);

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        $layanan = app(LayananKonfigurasi::class);
        $nilaiAwal = $layanan->ambil($organisasi->Id, 'Notifikasi.EmailAktif');
        $konteks->bersihkan();
        $this->assertTrue($nilaiAwal);

        $this->actingAs($admin)->put('/platform/konfigurasi/Notifikasi.EmailAktif', ['Nilai' => false]);

        $konteks->tetapkan($organisasi->Id);
        $nilaiBaru = $layanan->ambil($organisasi->Id, 'Notifikasi.EmailAktif');
        $konteks->bersihkan();
        $this->assertFalse($nilaiBaru);
    }

    public function test_pengguna_tanpa_izin_tidak_dapat_mengubah_konfigurasi(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $biasa = Pengguna::create([
            'OrganisasiId' => $organisasi->Id,
            'Nama' => 'Biasa',
            'Email' => 'biasa@amanpoll.test',
            'KataSandi' => 'rahasia',
            'Status' => 'Aktif',
        ]);

        $response = $this->actingAs($biasa)->put('/platform/konfigurasi/Notifikasi.EmailAktif', ['Nilai' => false]);

        $response->assertForbidden();
    }

    public function test_konfirmasi_penerima_bawaan_tidak_wajib_dan_admin_dapat_mewajibkannya(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $admin = $this->buatAdmin($organisasi);

        $this->actingAs($admin)->get('/platform/konfigurasi')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $inertia) => $inertia
                ->where('konfigurasi', fn (Collection $daftar): bool => $daftar->contains(
                    fn (array $item): bool => $item['Kunci'] === 'Pemeliharaan.WajibKonfirmasiPenerima'
                        && $item['Tipe'] === 'boolean'
                        && $item['Nilai'] === false
                        && $item['Label'] === 'Wajibkan konfirmasi penerima sebelum koordinator memverifikasi',
                )));

        $this->actingAs($admin)->put('/platform/konfigurasi/Pemeliharaan.WajibKonfirmasiPenerima', ['Nilai' => true])
            ->assertSessionDoesntHaveErrors();

        $this->assertTrue($this->nilai($organisasi, 'Pemeliharaan.WajibKonfirmasiPenerima'));
    }

    public function test_pengguna_tanpa_izin_pengaturan_tidak_dapat_mewajibkan_konfirmasi_penerima(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $biasa = Pengguna::create([
            'OrganisasiId' => $organisasi->Id,
            'Nama' => 'Teknisi',
            'Email' => 'teknisi@amanpoll.test',
            'KataSandi' => 'rahasia',
            'Status' => 'Aktif',
        ]);

        $this->actingAs($biasa)->put('/platform/konfigurasi/Pemeliharaan.WajibKonfirmasiPenerima', ['Nilai' => true])
            ->assertForbidden();

        $this->assertFalse($this->nilai($organisasi, 'Pemeliharaan.WajibKonfirmasiPenerima'));
    }

    private function nilai(Organisasi $organisasi, string $kunci): mixed
    {
        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);

        try {
            return app(LayananKonfigurasi::class)->ambil($organisasi->Id, $kunci);
        } finally {
            $konteks->bersihkan();
        }
    }
}
