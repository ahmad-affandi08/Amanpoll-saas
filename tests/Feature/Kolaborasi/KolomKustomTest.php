<?php

declare(strict_types=1);

namespace Tests\Feature\Kolaborasi;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\DefinisiKolomKustom;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\NilaiKolomKustom;
use App\Domain\Platform\Infrastructure\Persistence\Models\Izin;
use App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Domain\Platform\Infrastructure\Persistence\Models\PeranIzin;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenggunaPeran;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KolomKustomTest extends TestCase
{
    use RefreshDatabase;

    private function buatPengguna(Organisasi $organisasi, ?string $kodeIzin = 'Pengaturan.Kelola'): Pengguna
    {
        $pengguna = Pengguna::create([
            'OrganisasiId' => $organisasi->Id,
            'Nama' => 'Pengguna '.uniqid(),
            'Email' => 'pengguna+'.uniqid().'@amanpoll.test',
            'KataSandi' => 'rahasia',
            'Status' => 'Aktif',
        ]);

        if ($kodeIzin !== null) {
            $konteks = app(KonteksOrganisasi::class);
            $konteks->tetapkan($organisasi->Id);
            $izin = Izin::firstOrCreate(['Kode' => $kodeIzin], ['Nama' => $kodeIzin, 'Modul' => 'Pengaturan']);
            $peran = Peran::create(['Kode' => 'PERAN-'.uniqid(), 'Nama' => 'Peran']);
            PeranIzin::create(['PeranId' => $peran->Id, 'IzinId' => $izin->Id]);
            PenggunaPeran::create(['PenggunaId' => $pengguna->Id, 'PeranId' => $peran->Id]);
            $konteks->bersihkan();
        }

        return $pengguna;
    }

    private function buatLokasi(Organisasi $organisasi): Lokasi
    {
        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        $lokasi = Lokasi::create(['Kode' => 'LOK-'.uniqid(), 'Nama' => 'Lokasi Uji']);
        $konteks->bersihkan();

        return $lokasi;
    }

    public function test_admin_dapat_membuat_definisi_kolom_kustom(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $admin = $this->buatPengguna($organisasi);

        $this->actingAs($admin)->post('/kolaborasi/definisi-kolom-kustom', [
            'JenisEntitas' => 'Lokasi',
            'Kode' => 'kapasitas_maks',
            'Label' => 'Kapasitas Maksimum',
            'TipeData' => 'Angka',
            'Wajib' => true,
        ])->assertSessionDoesntHaveErrors();

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        $this->assertDatabaseHas('DefinisiKolomKustom', ['Kode' => 'kapasitas_maks', 'JenisEntitas' => 'Lokasi']);
        $konteks->bersihkan();
    }

    public function test_definisi_kolom_pilihan_wajib_punya_opsi(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $admin = $this->buatPengguna($organisasi);

        $this->actingAs($admin)->post('/kolaborasi/definisi-kolom-kustom', [
            'JenisEntitas' => 'Lokasi',
            'Kode' => 'kondisi',
            'Label' => 'Kondisi',
            'TipeData' => 'Pilihan',
        ])->assertSessionHasErrors('Pilihan');
    }

    public function test_kode_kolom_kustom_unik_per_jenis_entitas(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $admin = $this->buatPengguna($organisasi);

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        DefinisiKolomKustom::create(['JenisEntitas' => 'Lokasi', 'Kode' => 'kapasitas', 'Label' => 'Kapasitas', 'TipeData' => 'Angka']);
        $konteks->bersihkan();

        $this->actingAs($admin)->post('/kolaborasi/definisi-kolom-kustom', [
            'JenisEntitas' => 'Lokasi',
            'Kode' => 'kapasitas',
            'Label' => 'Kapasitas Lain',
            'TipeData' => 'Teks',
        ])->assertSessionHasErrors('Kode');
    }

    public function test_pengguna_tanpa_izin_kelola_lokasi_tidak_bisa_membuat_definisi(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $pengguna = $this->buatPengguna($organisasi, null);

        $this->actingAs($pengguna)->post('/kolaborasi/definisi-kolom-kustom', [
            'JenisEntitas' => 'Lokasi',
            'Kode' => 'kapasitas',
            'Label' => 'Kapasitas',
            'TipeData' => 'Angka',
        ])->assertForbidden();
    }

    public function test_simpan_nilai_kolom_kustom_untuk_lokasi(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $pengguna = $this->buatPengguna($organisasi);
        $lokasi = $this->buatLokasi($organisasi);

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        $definisi = DefinisiKolomKustom::create(['JenisEntitas' => 'Lokasi', 'Kode' => 'kapasitas', 'Label' => 'Kapasitas', 'TipeData' => 'Angka']);
        $konteks->bersihkan();

        $this->actingAs($pengguna)->post('/kolaborasi/nilai-kolom-kustom', [
            'DefinisiKolomKustomId' => $definisi->Id,
            'JenisEntitas' => 'Lokasi',
            'EntitasId' => $lokasi->Id,
            'Nilai' => 250,
        ])->assertSessionDoesntHaveErrors();

        $konteks->tetapkan($organisasi->Id);
        $nilai = NilaiKolomKustom::where('DefinisiKolomKustomId', $definisi->Id)->where('EntitasId', $lokasi->Id)->first();
        $konteks->bersihkan();

        $this->assertNotNull($nilai);
        $this->assertEquals(250, $nilai->Nilai);
    }

    public function test_simpan_nilai_ditolak_jika_tipe_data_tidak_sesuai(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $pengguna = $this->buatPengguna($organisasi);
        $lokasi = $this->buatLokasi($organisasi);

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        $definisi = DefinisiKolomKustom::create(['JenisEntitas' => 'Lokasi', 'Kode' => 'kapasitas', 'Label' => 'Kapasitas', 'TipeData' => 'Angka']);
        $konteks->bersihkan();

        $response = $this->actingAs($pengguna)->post('/kolaborasi/nilai-kolom-kustom', [
            'DefinisiKolomKustomId' => $definisi->Id,
            'JenisEntitas' => 'Lokasi',
            'EntitasId' => $lokasi->Id,
            'Nilai' => 'bukan angka',
        ]);

        $response->assertStatus(422);
    }

    public function test_simpan_nilai_wajib_ditolak_jika_kosong(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $pengguna = $this->buatPengguna($organisasi);
        $lokasi = $this->buatLokasi($organisasi);

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        $definisi = DefinisiKolomKustom::create(['JenisEntitas' => 'Lokasi', 'Kode' => 'kapasitas', 'Label' => 'Kapasitas', 'TipeData' => 'Angka', 'Wajib' => true]);
        $konteks->bersihkan();

        $response = $this->actingAs($pengguna)->post('/kolaborasi/nilai-kolom-kustom', [
            'DefinisiKolomKustomId' => $definisi->Id,
            'JenisEntitas' => 'Lokasi',
            'EntitasId' => $lokasi->Id,
            'Nilai' => null,
        ]);

        $response->assertStatus(422);
    }

    public function test_simpan_nilai_pilihan_harus_salah_satu_opsi(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $pengguna = $this->buatPengguna($organisasi);
        $lokasi = $this->buatLokasi($organisasi);

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        $definisi = DefinisiKolomKustom::create([
            'JenisEntitas' => 'Lokasi', 'Kode' => 'kondisi', 'Label' => 'Kondisi', 'TipeData' => 'Pilihan',
            'Pilihan' => ['Baik', 'Rusak'],
        ]);
        $konteks->bersihkan();

        $this->actingAs($pengguna)->post('/kolaborasi/nilai-kolom-kustom', [
            'DefinisiKolomKustomId' => $definisi->Id,
            'JenisEntitas' => 'Lokasi',
            'EntitasId' => $lokasi->Id,
            'Nilai' => 'Baik',
        ])->assertSessionDoesntHaveErrors();

        $response = $this->actingAs($pengguna)->post('/kolaborasi/nilai-kolom-kustom', [
            'DefinisiKolomKustomId' => $definisi->Id,
            'JenisEntitas' => 'Lokasi',
            'EntitasId' => $lokasi->Id,
            'Nilai' => 'TidakAda',
        ]);
        $response->assertStatus(422);
    }

    public function test_hapus_definisi_juga_menghapus_nilai_terkait(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $admin = $this->buatPengguna($organisasi);
        $lokasi = $this->buatLokasi($organisasi);

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        $definisi = DefinisiKolomKustom::create(['JenisEntitas' => 'Lokasi', 'Kode' => 'kapasitas', 'Label' => 'Kapasitas', 'TipeData' => 'Angka']);
        NilaiKolomKustom::create(['DefinisiKolomKustomId' => $definisi->Id, 'JenisEntitas' => 'Lokasi', 'EntitasId' => $lokasi->Id, 'Nilai' => 100]);
        $konteks->bersihkan();

        $this->actingAs($admin)->delete("/kolaborasi/definisi-kolom-kustom/{$definisi->Id}")
            ->assertSessionDoesntHaveErrors();

        $konteks->tetapkan($organisasi->Id);
        $this->assertDatabaseMissing('NilaiKolomKustom', ['DefinisiKolomKustomId' => $definisi->Id]);
        $konteks->bersihkan();
    }
}
