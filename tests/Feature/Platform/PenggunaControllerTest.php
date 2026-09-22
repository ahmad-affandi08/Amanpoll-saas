<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Aset\Domain\Enums\KondisiAset;
use App\Domain\Aset\Domain\Enums\StatusAset;
use App\Domain\Aset\Domain\Enums\TingkatKritisAset;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Aset\Infrastructure\Persistence\Models\KategoriAset;
use App\Domain\Aset\Infrastructure\Persistence\Models\RiwayatPenanggungJawabAset;
use App\Domain\IntegrasiAudit\Infrastructure\Persistence\Models\CatatanAkses;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PenugasanPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\WaktuKerja;
use App\Domain\Platform\Infrastructure\Persistence\Models\Izin;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenggunaPeran;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Domain\Platform\Infrastructure\Persistence\Models\PeranIzin;
use App\Domain\Platform\Infrastructure\Persistence\Models\UnitOrganisasi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
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
        $unitB = UnitOrganisasi::create(['Kode' => 'UNIT-B', 'Nama' => 'Unit B']);
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

    /**
     * Daftar pengguna hanya menjawab "siapa saja dan perannya apa". Sebelum
     * menonaktifkan atau memindahkan seseorang, penyelia perlu tahu apa yang
     * sedang dipegangnya -- dan itu dulu tidak ada di layar mana pun.
     */
    public function test_detail_pengguna_menampilkan_ringkasan_beban_kerja(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-'.uniqid(), 'Nama' => 'Organisasi']);
        $admin = $this->buatAdmin($organisasi);
        $teknisi = Pengguna::create([
            'OrganisasiId' => $organisasi->Id,
            'Nama' => 'Teknisi Lapangan',
            'Email' => 'teknisi+'.uniqid().'@amanpoll.test',
            'KataSandi' => 'rahasia',
            'Jabatan' => 'Teknisi Senior',
            'Status' => 'Aktif',
        ]);

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);

        $berjalan = $this->buatPerintahKerja('PK-8001');
        $selesai = $this->buatPerintahKerja('PK-8002');

        PenugasanPerintahKerja::create([
            'PerintahKerjaId' => $berjalan->Id,
            'PenggunaId' => $teknisi->Id,
            'DitugaskanPada' => now()->subDays(2),
        ]);
        PenugasanPerintahKerja::create([
            'PerintahKerjaId' => $selesai->Id,
            'PenggunaId' => $teknisi->Id,
            'DitugaskanPada' => now()->subDays(5),
            'SelesaiPada' => now()->subDays(4),
            'Status' => 'Selesai',
        ]);

        foreach ([90, 45] as $menit) {
            WaktuKerja::create([
                'PerintahKerjaId' => $berjalan->Id,
                'PenggunaId' => $teknisi->Id,
                'MulaiPada' => now()->subDays(2),
                'SelesaiPada' => now()->subDays(2)->addMinutes($menit),
                'DurasiMenit' => $menit,
            ]);
        }

        $aset = $this->buatAset('AST-8001', 'Pompa Utama');
        RiwayatPenanggungJawabAset::create([
            'AsetId' => $aset->Id,
            'PenggunaId' => $teknisi->Id,
            'MulaiPada' => now()->subMonth(),
        ]);
        RiwayatPenanggungJawabAset::create([
            'AsetId' => $this->buatAset('AST-8002', 'Pompa Cadangan')->Id,
            'PenggunaId' => $teknisi->Id,
            'MulaiPada' => now()->subYear(),
            'SelesaiPada' => now()->subMonths(6),
        ]);

        $konteks->bersihkan();

        $this->actingAs($admin)->get("/platform/pengguna/{$teknisi->Id}")
            ->assertOk()
            ->assertInertia(fn ($halaman) => $halaman
                ->component('Pengguna/Show')
                ->where('pengguna.Nama', 'Teknisi Lapangan')
                // Hanya penugasan dan tanggung jawab yang belum ditutup yang dihitung berjalan.
                ->where('ringkasan.PenugasanBerjalan', 1)
                ->where('ringkasan.TotalMenitKerja', 135)
                ->where('ringkasan.AsetDitanggung', 1)
                ->etc());
    }

    public function test_beban_kerja_pengguna_mengumpulkan_penugasan_waktu_dan_aset(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-'.uniqid(), 'Nama' => 'Organisasi']);
        $admin = $this->buatAdmin($organisasi);
        $teknisi = Pengguna::create([
            'OrganisasiId' => $organisasi->Id,
            'Nama' => 'Teknisi',
            'Email' => 'teknisi+'.uniqid().'@amanpoll.test',
            'KataSandi' => 'rahasia',
            'Status' => 'Aktif',
        ]);

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);

        $perintah = $this->buatPerintahKerja('PK-8100', 'Ganti seal pompa');
        PenugasanPerintahKerja::create([
            'PerintahKerjaId' => $perintah->Id,
            'PenggunaId' => $teknisi->Id,
            'DitugaskanPada' => now()->subDay(),
        ]);
        WaktuKerja::create([
            'PerintahKerjaId' => $perintah->Id,
            'PenggunaId' => $teknisi->Id,
            'MulaiPada' => now()->subDay(),
            'SelesaiPada' => now()->subDay()->addHour(),
            'DurasiMenit' => 60,
        ]);
        RiwayatPenanggungJawabAset::create([
            'AsetId' => $this->buatAset('AST-8100', 'Pompa Sekunder')->Id,
            'PenggunaId' => $teknisi->Id,
            'MulaiPada' => now()->subWeek(),
        ]);

        $konteks->bersihkan();

        $respons = $this->actingAs($admin)->getJson("/platform/pengguna/{$teknisi->Id}/beban-kerja");

        $respons->assertOk();
        $respons->assertJsonPath('ringkasan.JumlahPenugasanBerjalan', 1);
        $respons->assertJsonPath('ringkasan.TotalMenitKerja', 60);
        // Nomor dan judul perintah kerja ikut dibawa supaya barisnya terbaca tanpa membuka modul lain.
        $respons->assertJsonPath('penugasan.data.0.Nomor', 'PK-8100');
        $respons->assertJsonPath('penugasan.data.0.Judul', 'Ganti seal pompa');
        $respons->assertJsonPath('waktuKerja.data.0.DurasiMenit', 60);
        $respons->assertJsonPath('tanggungJawabAset.data.0.Aset', 'Pompa Sekunder');
    }

    public function test_aktivitas_pengguna_memisahkan_akses_berhasil_dan_gagal(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-'.uniqid(), 'Nama' => 'Organisasi']);
        $admin = $this->buatAdmin($organisasi);
        $teknisi = Pengguna::create([
            'OrganisasiId' => $organisasi->Id,
            'Nama' => 'Teknisi',
            'Email' => 'teknisi+'.uniqid().'@amanpoll.test',
            'KataSandi' => 'rahasia',
            'Status' => 'Aktif',
        ]);

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);

        CatatanAkses::create([
            'PenggunaId' => $teknisi->Id,
            'Jenis' => 'Masuk',
            'AlamatIp' => '10.0.0.1',
            'Berhasil' => true,
        ]);
        CatatanAkses::create([
            'PenggunaId' => $teknisi->Id,
            'Jenis' => 'Masuk',
            'AlamatIp' => '10.0.0.9',
            'Berhasil' => false,
            'AlasanGagal' => 'Kata sandi salah',
        ]);

        $konteks->bersihkan();

        $respons = $this->actingAs($admin)->getJson("/platform/pengguna/{$teknisi->Id}/aktivitas");

        $respons->assertOk();
        $respons->assertJsonPath('ringkasan.JumlahAkses', 2);
        $respons->assertJsonPath('ringkasan.JumlahAksesGagal', 1);
        $respons->assertJsonCount(2, 'akses.data');
    }

    public function test_detail_pengguna_organisasi_lain_tidak_dapat_dibuka(): void
    {
        $organisasiA = Organisasi::create(['Kode' => 'ORG-'.uniqid(), 'Nama' => 'Organisasi A']);
        $organisasiB = Organisasi::create(['Kode' => 'ORG-'.uniqid(), 'Nama' => 'Organisasi B']);
        $adminB = $this->buatAdmin($organisasiB);
        $penggunaA = Pengguna::create([
            'OrganisasiId' => $organisasiA->Id,
            'Nama' => 'Orang Lain',
            'Email' => 'lain+'.uniqid().'@amanpoll.test',
            'KataSandi' => 'rahasia',
            'Status' => 'Aktif',
        ]);

        $this->actingAs($adminB)->get("/platform/pengguna/{$penggunaA->Id}")->assertNotFound();
        $this->actingAs($adminB)
            ->getJson("/platform/pengguna/{$penggunaA->Id}/aktivitas")
            ->assertNotFound();
    }

    public function test_detail_pengguna_ditolak_tanpa_izin_kelola(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-'.uniqid(), 'Nama' => 'Organisasi']);
        $tanpaIzin = Pengguna::create([
            'OrganisasiId' => $organisasi->Id,
            'Nama' => 'Tanpa Izin',
            'Email' => 'tanpa+'.uniqid().'@amanpoll.test',
            'KataSandi' => 'rahasia',
            'Status' => 'Aktif',
        ]);

        $this->actingAs($tanpaIzin)->get("/platform/pengguna/{$tanpaIzin->Id}")->assertForbidden();
        $this->actingAs($tanpaIzin)
            ->getJson("/platform/pengguna/{$tanpaIzin->Id}/beban-kerja")
            ->assertForbidden();
    }

    private function buatPerintahKerja(string $nomor, string $judul = 'Perbaikan'): PerintahKerja
    {
        return PerintahKerja::create([
            'Nomor' => $nomor,
            'Jenis' => 'Korektif',
            'Judul' => $judul,
            'Prioritas' => 'Normal',
            'Status' => 'Dibuka',
            'Versi' => 1,
        ]);
    }

    private function buatAset(string $kode, string $nama): Aset
    {
        $kategori = KategoriAset::firstOrCreate(['Kode' => 'KAT-UJI'], ['Nama' => 'Kategori Uji']);

        return Aset::create([
            'KategoriAsetId' => $kategori->Id,
            'KodeAset' => $kode,
            'Nama' => $nama,
            'Status' => StatusAset::Aktif->value,
            'Kondisi' => KondisiAset::Baik->value,
            'TingkatKritis' => TingkatKritisAset::Normal->value,
            'KodeQr' => (string) Str::ulid(),
            'Versi' => 1,
        ]);
    }
}
