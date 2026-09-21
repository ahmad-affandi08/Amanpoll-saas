<?php

declare(strict_types=1);

namespace Tests\Feature\Kolaborasi;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\Berkas;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\LampiranEntitas;
use App\Domain\Platform\Infrastructure\Persistence\Models\Izin;
use App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenggunaPeran;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Domain\Platform\Infrastructure\Persistence\Models\PeranIzin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BerkasLampiranTest extends TestCase
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

    public function test_unggah_berkas_berhasil_dengan_nama_penyimpanan_aman(): void
    {
        Storage::fake('local');
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $pengguna = $this->buatPengguna($organisasi, null);

        $response = $this->actingAs($pengguna)->post('/kolaborasi/berkas', [
            'Berkas' => UploadedFile::fake()->create('../../../etc/dokumen berbahaya.pdf', 100, 'application/pdf'),
        ]);

        $response->assertSessionDoesntHaveErrors();
        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        $berkas = Berkas::first();
        $konteks->bersihkan();

        $this->assertNotNull($berkas);
        $this->assertSame('dokumen berbahaya.pdf', $berkas->NamaAsli);
        $this->assertStringNotContainsString('..', $berkas->LokasiPenyimpanan);
        $this->assertStringNotContainsString('dokumen berbahaya', $berkas->NamaPenyimpanan);
        Storage::disk('local')->assertExists($berkas->LokasiPenyimpanan);
    }

    public function test_unggah_berkas_sekaligus_lampirkan_ke_lokasi_dalam_satu_permintaan(): void
    {
        Storage::fake('local');
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $pengguna = $this->buatPengguna($organisasi);
        $lokasi = $this->buatLokasi($organisasi);

        $response = $this->actingAs($pengguna)->post('/kolaborasi/berkas', [
            'Berkas' => UploadedFile::fake()->create('foto.pdf', 100, 'application/pdf'),
            'JenisEntitas' => 'Lokasi',
            'EntitasId' => $lokasi->Id,
        ]);

        $response->assertSessionDoesntHaveErrors();

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        $berkas = Berkas::first();
        $lampiran = LampiranEntitas::where('EntitasId', $lokasi->Id)->first();
        $konteks->bersihkan();

        $this->assertNotNull($berkas);
        $this->assertNotNull($lampiran);
        $this->assertSame($berkas->Id, $lampiran->BerkasId);
    }

    public function test_unggah_berkas_dengan_lampiran_ditolak_tanpa_izin_kelola_entitas(): void
    {
        Storage::fake('local');
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $pengguna = $this->buatPengguna($organisasi, null);
        $lokasi = $this->buatLokasi($organisasi);

        $response = $this->actingAs($pengguna)->post('/kolaborasi/berkas', [
            'Berkas' => UploadedFile::fake()->create('foto.pdf', 100, 'application/pdf'),
            'JenisEntitas' => 'Lokasi',
            'EntitasId' => $lokasi->Id,
        ]);

        $response->assertForbidden();
    }

    public function test_unggah_berkas_ditolak_untuk_mime_tidak_diizinkan(): void
    {
        Storage::fake('local');
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $pengguna = $this->buatPengguna($organisasi, null);

        $response = $this->actingAs($pengguna)->post('/kolaborasi/berkas', [
            'Berkas' => UploadedFile::fake()->create('skrip.exe', 100, 'application/x-msdownload'),
        ]);

        $response->assertSessionHasErrors('Berkas');
    }

    public function test_unggah_berkas_ditolak_saat_melebihi_batas_ukuran(): void
    {
        Storage::fake('local');
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $pengguna = $this->buatPengguna($organisasi, null);

        $response = $this->actingAs($pengguna)->post('/kolaborasi/berkas', [
            'Berkas' => UploadedFile::fake()->create('besar.pdf', 10241, 'application/pdf'),
        ]);

        $response->assertSessionHasErrors('Berkas');
    }

    public function test_lampirkan_berkas_ke_lokasi_berhasil_dan_bisa_dilihat(): void
    {
        Storage::fake('local');
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $pengguna = $this->buatPengguna($organisasi);
        $lokasi = $this->buatLokasi($organisasi);

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        $berkas = Berkas::create([
            'NamaAsli' => 'foto.jpg', 'NamaPenyimpanan' => 'x.jpg', 'MediaPenyimpanan' => 'local',
            'LokasiPenyimpanan' => 'berkas/x.jpg', 'JenisMime' => 'image/jpeg',
        ]);
        $konteks->bersihkan();

        $response = $this->actingAs($pengguna)->post('/kolaborasi/lampiran', [
            'JenisEntitas' => 'Lokasi',
            'EntitasId' => $lokasi->Id,
            'BerkasId' => $berkas->Id,
            'Kategori' => 'Foto',
        ]);

        $response->assertSessionDoesntHaveErrors();

        $konteks->tetapkan($organisasi->Id);
        $this->assertDatabaseHas('LampiranEntitas', ['JenisEntitas' => 'Lokasi', 'EntitasId' => $lokasi->Id, 'BerkasId' => $berkas->Id]);
        $konteks->bersihkan();
    }

    public function test_lampirkan_berkas_ditolak_tanpa_izin_kelola_entitas(): void
    {
        Storage::fake('local');
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $pengguna = $this->buatPengguna($organisasi, null);
        $lokasi = $this->buatLokasi($organisasi);

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        $berkas = Berkas::create([
            'NamaAsli' => 'foto.jpg', 'NamaPenyimpanan' => 'x.jpg', 'MediaPenyimpanan' => 'local',
            'LokasiPenyimpanan' => 'berkas/x.jpg', 'JenisMime' => 'image/jpeg',
        ]);
        $konteks->bersihkan();

        $response = $this->actingAs($pengguna)->post('/kolaborasi/lampiran', [
            'JenisEntitas' => 'Lokasi',
            'EntitasId' => $lokasi->Id,
            'BerkasId' => $berkas->Id,
        ]);

        $response->assertForbidden();
    }

    public function test_lampirkan_ke_entitas_lintas_organisasi_gagal(): void
    {
        Storage::fake('local');
        $organisasiA = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $organisasiB = Organisasi::create(['Kode' => 'ORG-B', 'Nama' => 'Organisasi B']);
        $pengguna = $this->buatPengguna($organisasiA);
        $lokasiB = $this->buatLokasi($organisasiB);

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasiA->Id);
        $berkas = Berkas::create([
            'NamaAsli' => 'foto.jpg', 'NamaPenyimpanan' => 'x.jpg', 'MediaPenyimpanan' => 'local',
            'LokasiPenyimpanan' => 'berkas/x.jpg', 'JenisMime' => 'image/jpeg',
        ]);
        $konteks->bersihkan();

        $response = $this->actingAs($pengguna)->post('/kolaborasi/lampiran', [
            'JenisEntitas' => 'Lokasi',
            'EntitasId' => $lokasiB->Id,
            'BerkasId' => $berkas->Id,
        ]);

        $response->assertNotFound();
    }

    public function test_unduh_berkas_yang_dilampirkan_diizinkan_untuk_pemegang_izin_entitas(): void
    {
        Storage::fake('local');
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $pemilik = $this->buatPengguna($organisasi);
        $lain = $this->buatPengguna($organisasi);
        $lokasi = $this->buatLokasi($organisasi);

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        $berkas = Berkas::create([
            'NamaAsli' => 'foto.jpg', 'NamaPenyimpanan' => 'x.jpg', 'MediaPenyimpanan' => 'local',
            'LokasiPenyimpanan' => 'berkas/x.jpg', 'JenisMime' => 'image/jpeg', 'DiunggahOleh' => $pemilik->Id,
        ]);
        Storage::disk('local')->put('berkas/x.jpg', 'isi-file');
        LampiranEntitas::create(['JenisEntitas' => 'Lokasi', 'EntitasId' => $lokasi->Id, 'BerkasId' => $berkas->Id]);
        $konteks->bersihkan();

        $response = $this->actingAs($lain)->get("/kolaborasi/berkas/{$berkas->Id}/unduh");

        $response->assertOk();
    }

    public function test_unduh_berkas_ditolak_untuk_pengguna_tanpa_akses_apapun(): void
    {
        Storage::fake('local');
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $pemilik = $this->buatPengguna($organisasi, null);
        $lain = $this->buatPengguna($organisasi, null);

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        $berkas = Berkas::create([
            'NamaAsli' => 'foto.jpg', 'NamaPenyimpanan' => 'x.jpg', 'MediaPenyimpanan' => 'local',
            'LokasiPenyimpanan' => 'berkas/x.jpg', 'JenisMime' => 'image/jpeg', 'DiunggahOleh' => $pemilik->Id,
        ]);
        $konteks->bersihkan();

        $response = $this->actingAs($lain)->get("/kolaborasi/berkas/{$berkas->Id}/unduh");

        $response->assertForbidden();
    }

    public function test_hapus_berkas_juga_menghapus_lampirannya(): void
    {
        Storage::fake('local');
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $pengguna = $this->buatPengguna($organisasi);
        $lokasi = $this->buatLokasi($organisasi);

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        $berkas = Berkas::create([
            'NamaAsli' => 'foto.jpg', 'NamaPenyimpanan' => 'x.jpg', 'MediaPenyimpanan' => 'local',
            'LokasiPenyimpanan' => 'berkas/x.jpg', 'JenisMime' => 'image/jpeg', 'DiunggahOleh' => $pengguna->Id,
        ]);
        Storage::disk('local')->put('berkas/x.jpg', 'isi-file');
        LampiranEntitas::create(['JenisEntitas' => 'Lokasi', 'EntitasId' => $lokasi->Id, 'BerkasId' => $berkas->Id]);
        $konteks->bersihkan();

        $response = $this->actingAs($pengguna)->delete("/kolaborasi/berkas/{$berkas->Id}");

        $response->assertSessionDoesntHaveErrors();
        Storage::disk('local')->assertMissing('berkas/x.jpg');

        $konteks->tetapkan($organisasi->Id);
        $this->assertDatabaseMissing('LampiranEntitas', ['BerkasId' => $berkas->Id]);
        $this->assertSoftDeleted($berkas);
        $konteks->bersihkan();
    }

    public function test_lepas_lampiran_ditolak_tanpa_izin_kelola_entitas(): void
    {
        Storage::fake('local');
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $pemilik = $this->buatPengguna($organisasi);
        $tanpaIzin = $this->buatPengguna($organisasi, null);
        $lokasi = $this->buatLokasi($organisasi);

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        $berkas = Berkas::create([
            'NamaAsli' => 'foto.jpg', 'NamaPenyimpanan' => 'x.jpg', 'MediaPenyimpanan' => 'local',
            'LokasiPenyimpanan' => 'berkas/x.jpg', 'JenisMime' => 'image/jpeg', 'DiunggahOleh' => $pemilik->Id,
        ]);
        $lampiran = LampiranEntitas::create(['JenisEntitas' => 'Lokasi', 'EntitasId' => $lokasi->Id, 'BerkasId' => $berkas->Id]);
        $konteks->bersihkan();

        $response = $this->actingAs($tanpaIzin)->delete("/kolaborasi/lampiran/{$lampiran->Id}");

        $response->assertForbidden();
    }
}
