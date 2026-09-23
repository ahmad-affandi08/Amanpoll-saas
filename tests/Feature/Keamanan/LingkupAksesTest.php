<?php

declare(strict_types=1);

namespace Tests\Feature\Keamanan;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Aset\Infrastructure\Persistence\Models\KategoriAset;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\Keluhan;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\Anggaran;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\Gudang;
use App\Domain\Platform\Application\Actions\TetapkanPeranKePengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\Izin;
use App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenggunaPeran;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Domain\Platform\Infrastructure\Persistence\Models\PeranIzin;
use App\Domain\Platform\Infrastructure\Persistence\Models\UnitOrganisasi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Batas data per unit dan ruangan.
 *
 * PenggunaPeran sejak awal menyimpan cakupan, tetapi sampai versi ini tidak
 * ada kueri yang membacanya: penugasan "Teknisi, Poli Umum" tampak terbatas di
 * layar padahal penggunanya melihat seluruh rumah sakit.
 */
class LingkupAksesTest extends TestCase
{
    use RefreshDatabase;

    private Organisasi $organisasi;

    private KategoriAset $kategori;

    private Peran $peran;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organisasi = Organisasi::create(['Kode' => 'ORG-LK', 'Nama' => 'RS Uji', 'Status' => 'Aktif']);
        app(KonteksOrganisasi::class)->tetapkan($this->organisasi->Id);

        $this->kategori = KategoriAset::create(['Nama' => 'Alat Medis']);
        $this->peran = Peran::create(['Kode' => 'STAF', 'Nama' => 'Staf']);

        foreach (['Aset.Lihat', 'Keluhan.Kelola', 'Pengaturan.Kelola'] as $kode) {
            $izin = Izin::firstOrCreate(['Kode' => $kode], ['Nama' => $kode, 'Modul' => 'Uji']);
            PeranIzin::create(['PeranId' => $this->peran->Id, 'IzinId' => $izin->Id]);
        }
    }

    /** Tenant yang sudah berjalan seluruh penugasannya tanpa cakupan; aksesnya tidak boleh berubah. */
    public function test_penugasan_tanpa_cakupan_melihat_seluruh_organisasi(): void
    {
        $unit = UnitOrganisasi::create(['Kode' => 'U1', 'Nama' => 'Radiologi', 'Jenis' => 'Instalasi', 'Status' => 'Aktif']);
        $this->buatAset('Aset Radiologi', unit: $unit);
        $this->buatAset('Aset Tanpa Unit');

        $pengguna = $this->buatPengguna();

        $this->berlaku($pengguna, fn () => $this->assertSame(2, Aset::query()->count()));
    }

    public function test_penugasan_berunit_hanya_melihat_aset_unit_itu(): void
    {
        $radiologi = UnitOrganisasi::create(['Kode' => 'U1', 'Nama' => 'Radiologi', 'Jenis' => 'Instalasi', 'Status' => 'Aktif']);
        $bedah = UnitOrganisasi::create(['Kode' => 'U2', 'Nama' => 'Bedah', 'Jenis' => 'Instalasi', 'Status' => 'Aktif']);

        $this->buatAset('Aset Radiologi', unit: $radiologi);
        $this->buatAset('Aset Bedah', unit: $bedah);

        $pengguna = $this->buatPengguna(unit: $radiologi);

        $this->berlaku($pengguna, function (): void {
            $this->assertSame(['Aset Radiologi'], Aset::query()->pluck('Nama')->all());
        });
    }

    /** Menugaskan orang ke instalasi berarti ikut seluruh sub-unitnya. */
    public function test_sub_unit_ikut_terlihat(): void
    {
        $induk = UnitOrganisasi::create(['Kode' => 'U1', 'Nama' => 'Penunjang', 'Jenis' => 'Instalasi', 'Status' => 'Aktif']);
        $anak = UnitOrganisasi::create(['Kode' => 'U1A', 'Nama' => 'Laboratorium', 'Jenis' => 'Sub', 'Status' => 'Aktif', 'IndukId' => $induk->Id]);
        $cucu = UnitOrganisasi::create(['Kode' => 'U1B', 'Nama' => 'Patologi', 'Jenis' => 'Sub', 'Status' => 'Aktif', 'IndukId' => $anak->Id]);

        $this->buatAset('Aset Cucu', unit: $cucu);

        $pengguna = $this->buatPengguna(unit: $induk);

        $this->berlaku($pengguna, fn () => $this->assertSame(1, Aset::query()->count()));
    }

    /** Ruangan milik unit yang diizinkan ikut terbawa tanpa perlu ditugaskan satu per satu. */
    public function test_ruangan_milik_unit_yang_diizinkan_ikut_terbawa(): void
    {
        $unit = UnitOrganisasi::create(['Kode' => 'U1', 'Nama' => 'Rawat Inap', 'Jenis' => 'Instalasi', 'Status' => 'Aktif']);
        $ruang = Lokasi::create(['Kode' => 'R1', 'Nama' => 'Kamar 101', 'UnitOrganisasiId' => $unit->Id]);

        // Aset hanya punya ruangan, unitnya kosong.
        $this->buatAset('Ranjang', lokasi: $ruang);

        $pengguna = $this->buatPengguna(unit: $unit);

        $this->berlaku($pengguna, fn () => $this->assertSame(1, Aset::query()->count()));
    }

    public function test_penugasan_berlokasi_hanya_melihat_ruangan_itu(): void
    {
        $poli = Lokasi::create(['Kode' => 'R1', 'Nama' => 'Poli Umum']);
        $igd = Lokasi::create(['Kode' => 'R2', 'Nama' => 'IGD']);

        $this->buatAset('Tensi Poli', lokasi: $poli);
        $this->buatAset('Monitor IGD', lokasi: $igd);

        $pengguna = $this->buatPengguna(lokasi: $poli);

        $this->berlaku($pengguna, function (): void {
            $this->assertSame(['Tensi Poli'], Aset::query()->pluck('Nama')->all());
        });
    }

    /** Satu penugasan tanpa cakupan membuka seluruhnya; itu aturan yang disengaja. */
    public function test_satu_penugasan_tanpa_cakupan_mengalahkan_yang_berlingkup(): void
    {
        $poli = Lokasi::create(['Kode' => 'R1', 'Nama' => 'Poli Umum']);
        $this->buatAset('Tensi Poli', lokasi: $poli);
        $this->buatAset('Aset Lain');

        $pengguna = $this->buatPengguna(lokasi: $poli);
        $peranKedua = Peran::create(['Kode' => 'MANAJER', 'Nama' => 'Manajer']);
        PenggunaPeran::create(['PenggunaId' => $pengguna->Id, 'PeranId' => $peranKedua->Id]);

        $this->berlaku($pengguna, fn () => $this->assertSame(2, Aset::query()->count()));
    }

    /** Fail-closed: cakupan yang tidak cocok apa pun berarti nol baris, bukan semua baris. */
    public function test_cakupan_tanpa_padanan_tidak_melihat_apa_pun(): void
    {
        $kosong = UnitOrganisasi::create(['Kode' => 'U9', 'Nama' => 'Unit Kosong', 'Jenis' => 'Instalasi', 'Status' => 'Aktif']);
        $lain = UnitOrganisasi::create(['Kode' => 'U1', 'Nama' => 'Radiologi', 'Jenis' => 'Instalasi', 'Status' => 'Aktif']);

        $this->buatAset('Aset Radiologi', unit: $lain);

        $pengguna = $this->buatPengguna(unit: $kosong);

        $this->berlaku($pengguna, fn () => $this->assertSame(0, Aset::query()->count()));
    }

    /**
     * Cabang fail-closed yang sebenarnya.
     *
     * Anggaran hanya dibatasi lewat unit. Pengguna yang cakupannya cuma
     * ruangan tidak punya satu pun unit yang diizinkan, sehingga tidak ada
     * syarat yang dapat dipasang -- dan tanpa penutup eksplisit kuerinya
     * justru lolos tanpa saringan dan membuka seluruh anggaran rumah sakit.
     */
    public function test_cakupan_ruangan_tidak_membuka_entitas_yang_dibatasi_unit(): void
    {
        $poli = Lokasi::create(['Kode' => 'R1', 'Nama' => 'Poli Umum']);
        $unit = UnitOrganisasi::create(['Kode' => 'U1', 'Nama' => 'Keuangan', 'Jenis' => 'Bagian', 'Status' => 'Aktif']);

        Anggaran::create([
            'UnitOrganisasiId' => $unit->Id,
            'Kode' => 'ANG-1',
            'Tahun' => 2026,
            'Nama' => 'Anggaran Pemeliharaan',
            'MataUang' => 'IDR',
            'Jumlah' => 100_000_000,
            'Status' => 'Aktif',
        ]);

        $pengguna = $this->buatPengguna(lokasi: $poli);

        $this->berlaku($pengguna, fn () => $this->assertSame(0, Anggaran::query()->count()));
    }

    /** Sebaliknya: cakupan unit tanpa ruangan tidak membuka gudang, yang dibatasi ruangan. */
    public function test_cakupan_unit_tanpa_ruangan_tidak_membuka_gudang(): void
    {
        $unit = UnitOrganisasi::create(['Kode' => 'U1', 'Nama' => 'Radiologi', 'Jenis' => 'Instalasi', 'Status' => 'Aktif']);
        $gudangPusat = Lokasi::create(['Kode' => 'R9', 'Nama' => 'Gudang Pusat']);

        Gudang::create(['Kode' => 'GDG-1', 'Nama' => 'Gudang Pusat', 'LokasiId' => $gudangPusat->Id]);

        // Unitnya tidak punya ruangan, jadi daftar lokasi yang diizinkan kosong.
        $pengguna = $this->buatPengguna(unit: $unit);

        $this->berlaku($pengguna, fn () => $this->assertSame(0, Gudang::query()->count()));
    }

    /** Aset tanpa unit dan tanpa ruangan tidak boleh bocor ke pengguna berlingkup. */
    public function test_aset_tanpa_unit_dan_ruangan_tidak_terlihat_pengguna_berlingkup(): void
    {
        $unit = UnitOrganisasi::create(['Kode' => 'U1', 'Nama' => 'Radiologi', 'Jenis' => 'Instalasi', 'Status' => 'Aktif']);
        $this->buatAset('Aset Yatim');

        $pengguna = $this->buatPengguna(unit: $unit);

        $this->berlaku($pengguna, fn () => $this->assertSame(0, Aset::query()->count()));
    }

    public function test_keluhan_ikut_dibatasi_ruangan(): void
    {
        $poli = Lokasi::create(['Kode' => 'R1', 'Nama' => 'Poli Umum']);
        $igd = Lokasi::create(['Kode' => 'R2', 'Nama' => 'IGD']);

        foreach ([[$poli, 'Rusak di Poli'], [$igd, 'Rusak di IGD']] as [$lokasi, $judul]) {
            Keluhan::create([
                'Nomor' => 'KLH-'.uniqid(),
                'LokasiId' => $lokasi->Id,
                'Judul' => $judul,
                'Deskripsi' => 'Uji',
            ]);
        }

        $pengguna = $this->buatPengguna(lokasi: $poli);

        $this->berlaku($pengguna, function (): void {
            $this->assertSame(['Rusak di Poli'], Keluhan::query()->pluck('Judul')->all());
        });
    }

    /** Daftar ruangan ikut menyempit, kalau tidak isian dropdown membocorkan seluruh denah. */
    public function test_daftar_lokasi_dan_unit_ikut_menyempit(): void
    {
        $unit = UnitOrganisasi::create(['Kode' => 'U1', 'Nama' => 'Radiologi', 'Jenis' => 'Instalasi', 'Status' => 'Aktif']);
        UnitOrganisasi::create(['Kode' => 'U2', 'Nama' => 'Bedah', 'Jenis' => 'Instalasi', 'Status' => 'Aktif']);
        Lokasi::create(['Kode' => 'R1', 'Nama' => 'Ruang Rontgen', 'UnitOrganisasiId' => $unit->Id]);
        Lokasi::create(['Kode' => 'R2', 'Nama' => 'Ruang Operasi']);

        $pengguna = $this->buatPengguna(unit: $unit);

        $this->berlaku($pengguna, function (): void {
            $this->assertSame(['Ruang Rontgen'], Lokasi::query()->pluck('Nama')->all());
            $this->assertSame(['Radiologi'], UnitOrganisasi::query()->pluck('Nama')->all());
        });
    }

    /** Batas harus berlaku pada route model binding juga, bukan hanya daftar. */
    public function test_aset_di_luar_lingkup_tidak_dapat_dibuka_lewat_url(): void
    {
        $radiologi = UnitOrganisasi::create(['Kode' => 'U1', 'Nama' => 'Radiologi', 'Jenis' => 'Instalasi', 'Status' => 'Aktif']);
        $bedah = UnitOrganisasi::create(['Kode' => 'U2', 'Nama' => 'Bedah', 'Jenis' => 'Instalasi', 'Status' => 'Aktif']);

        $milikOrang = $this->buatAset('Aset Bedah', unit: $bedah);
        $pengguna = $this->buatPengguna(unit: $radiologi);

        $this->actingAs($pengguna)->get('/aset/'.$milikOrang->Id)->assertNotFound();
    }

    public function test_daftar_aset_di_halaman_ikut_dibatasi(): void
    {
        $radiologi = UnitOrganisasi::create(['Kode' => 'U1', 'Nama' => 'Radiologi', 'Jenis' => 'Instalasi', 'Status' => 'Aktif']);
        $bedah = UnitOrganisasi::create(['Kode' => 'U2', 'Nama' => 'Bedah', 'Jenis' => 'Instalasi', 'Status' => 'Aktif']);

        $this->buatAset('Aset Radiologi', unit: $radiologi);
        $this->buatAset('Aset Bedah', unit: $bedah);

        $pengguna = $this->buatPengguna(unit: $radiologi);

        $this->actingAs($pengguna)->get('/aset')
            ->assertOk()
            ->assertInertia(fn ($props) => $props->has('aset.data', 1)->etc());
    }

    /** Antrian dan perintah terjadwal berjalan tanpa pengguna dan tidak boleh ikut dibatasi. */
    public function test_tanpa_pengguna_masuk_tidak_ada_pembatasan(): void
    {
        $unit = UnitOrganisasi::create(['Kode' => 'U1', 'Nama' => 'Radiologi', 'Jenis' => 'Instalasi', 'Status' => 'Aktif']);
        $this->buatAset('Aset Radiologi', unit: $unit);
        $this->buatAset('Aset Yatim');

        $this->assertSame(2, Aset::query()->count());
    }

    /** Cakupan berubah harus langsung berlaku, bukan menunggu cache lima menit habis. */
    public function test_perubahan_cakupan_langsung_berlaku(): void
    {
        $radiologi = UnitOrganisasi::create(['Kode' => 'U1', 'Nama' => 'Radiologi', 'Jenis' => 'Instalasi', 'Status' => 'Aktif']);
        $bedah = UnitOrganisasi::create(['Kode' => 'U2', 'Nama' => 'Bedah', 'Jenis' => 'Instalasi', 'Status' => 'Aktif']);

        $this->buatAset('Aset Bedah', unit: $bedah);
        $pengguna = $this->buatPengguna(unit: $radiologi);

        $this->berlaku($pengguna, fn () => $this->assertSame(0, Aset::query()->count()));

        app(TetapkanPeranKePengguna::class)
            ->jalankan($pengguna, $this->peran, $bedah->Id);

        $this->berlaku($pengguna, fn () => $this->assertSame(1, Aset::query()->count()));
    }

    /** Tenancy tetap lapisan pertama: lingkup tidak boleh membukanya. */
    public function test_lingkup_tidak_menembus_batas_organisasi(): void
    {
        $unit = UnitOrganisasi::create(['Kode' => 'U1', 'Nama' => 'Radiologi', 'Jenis' => 'Instalasi', 'Status' => 'Aktif']);
        $this->buatAset('Aset Kita', unit: $unit);
        $pengguna = $this->buatPengguna(unit: $unit);

        $lain = Organisasi::create(['Kode' => 'ORG-LAIN', 'Nama' => 'RS Lain', 'Status' => 'Aktif']);
        app(KonteksOrganisasi::class)->tetapkan($lain->Id);
        $this->actingAs($pengguna, 'web');

        $this->assertSame(0, Aset::query()->count());
    }

    /**
     * Penegakan ini tidak ada artinya bila admin tidak dapat menetapkan
     * cakupannya. Sebelum perubahan ini formulir peran hanya mengirim PeranId,
     * sehingga kolom cakupan di basis data selalu kosong.
     */
    public function test_cakupan_dapat_ditetapkan_lewat_rute_penugasan_peran(): void
    {
        $unit = UnitOrganisasi::create(['Kode' => 'U1', 'Nama' => 'Radiologi', 'Jenis' => 'Instalasi', 'Status' => 'Aktif']);
        $sasaran = $this->buatPengguna();

        $izin = Izin::firstOrCreate(['Kode' => 'Pengguna.Kelola'], ['Nama' => 'Kelola Pengguna', 'Modul' => 'IAM']);
        $peranAdmin = Peran::create(['Kode' => 'ADM', 'Nama' => 'Admin']);
        PeranIzin::create(['PeranId' => $peranAdmin->Id, 'IzinId' => $izin->Id]);

        $admin = Pengguna::create([
            'OrganisasiId' => $this->organisasi->Id,
            'Nama' => 'Admin',
            'Email' => 'admin+'.uniqid().'@amanpoll.test',
            'KataSandi' => 'rahasia',
            'Status' => 'Aktif',
        ]);
        PenggunaPeran::create(['PenggunaId' => $admin->Id, 'PeranId' => $peranAdmin->Id]);

        $this->actingAs($admin)
            ->post('/platform/pengguna/'.$sasaran->Id.'/peran', [
                'PeranId' => $this->peran->Id,
                'UnitOrganisasiId' => $unit->Id,
            ])
            ->assertSessionDoesntHaveErrors();

        app(KonteksOrganisasi::class)->tetapkan($this->organisasi->Id);
        $this->assertDatabaseHas('PenggunaPeran', [
            'PenggunaId' => $sasaran->Id,
            'UnitOrganisasiId' => $unit->Id,
        ]);
    }

    /** Menjalankan sesuatu seolah pengguna itu yang sedang masuk. */
    private function berlaku(Pengguna $pengguna, callable $aksi): void
    {
        $this->actingAs($pengguna, 'web');

        try {
            $aksi();
        } finally {
            app('auth')->guard('web')->logout();
        }
    }

    private function buatAset(string $nama, ?UnitOrganisasi $unit = null, ?Lokasi $lokasi = null): Aset
    {
        return Aset::create([
            'KategoriAsetId' => $this->kategori->Id,
            'UnitOrganisasiId' => $unit?->Id,
            'LokasiId' => $lokasi?->Id,
            'Nama' => $nama,
            'Status' => 'Aktif',
            'Kondisi' => 'Baik',
        ]);
    }

    private function buatPengguna(?UnitOrganisasi $unit = null, ?Lokasi $lokasi = null): Pengguna
    {
        $pengguna = Pengguna::create([
            'OrganisasiId' => $this->organisasi->Id,
            'Nama' => 'Staf',
            'Email' => 'staf+'.uniqid().'@amanpoll.test',
            'KataSandi' => 'rahasia',
            'Status' => 'Aktif',
        ]);

        PenggunaPeran::create([
            'PenggunaId' => $pengguna->Id,
            'PeranId' => $this->peran->Id,
            'UnitOrganisasiId' => $unit?->Id,
            'LokasiId' => $lokasi?->Id,
        ]);

        return $pengguna;
    }
}
