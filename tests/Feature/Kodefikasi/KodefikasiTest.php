<?php

declare(strict_types=1);

namespace Tests\Feature\Kodefikasi;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Aset\Infrastructure\Persistence\Models\KategoriAset;
use App\Domain\Kodefikasi\Application\Actions\TetapkanKodeBarang;
use App\Domain\Kodefikasi\Application\Services\PenyusunKodeRegistrasi;
use App\Domain\Kodefikasi\Domain\Enums\StandarKodefikasi;
use App\Domain\Kodefikasi\Infrastructure\Persistence\Models\KodeBarang;
use App\Domain\Kodefikasi\Infrastructure\Persistence\Models\KodeBarangAset;
use App\Domain\Platform\Infrastructure\Persistence\Models\Izin;
use App\Domain\Platform\Infrastructure\Persistence\Models\KonfigurasiOrganisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenggunaPeran;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Domain\Platform\Infrastructure\Persistence\Models\PeranIzin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Kodefikasi barang milik negara (PMK 29/2010) dan daerah (Permendagri 108/2016).
 */
class KodefikasiTest extends TestCase
{
    use RefreshDatabase;

    private Organisasi $organisasi;

    private KategoriAset $kategori;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organisasi = Organisasi::create(['Kode' => 'ORG-KDF', 'Nama' => 'RS Uji', 'Status' => 'Aktif']);
        app(KonteksOrganisasi::class)->tetapkan($this->organisasi->Id);

        $this->kategori = KategoriAset::create(['Nama' => 'Alat Medis']);
    }

    /** @return array<string, array{string, bool}> */
    public static function kodeBmn(): array
    {
        return [
            'sepuluh angka lima ruas' => ['3.05.01.04.001', true],
            'kurang satu ruas' => ['3.05.01.04', false],
            'ruas terakhir dua angka' => ['3.05.01.04.01', false],
            'golongan dua angka' => ['33.05.01.04.001', false],
            'tanpa titik' => ['3050104001', false],
            'berisi huruf' => ['3.05.01.04.00A', false],
        ];
    }

    #[DataProvider('kodeBmn')]
    public function test_pola_kode_simak_bmn_ditegakkan(string $kode, bool $diharapkan): void
    {
        $this->assertSame($diharapkan, StandarKodefikasi::SimakBmn->cocok($kode));
    }

    /** Format daerah sengaja longgar; digitnya tidak dapat dipastikan dari sumber primer. */
    public function test_pola_kode_simbada_menerima_hierarki_tujuh_tingkat(): void
    {
        $this->assertTrue(StandarKodefikasi::Simbada->cocok('1.3.2.06.01.01.001'));
        $this->assertTrue(StandarKodefikasi::Simbada->cocok('1.3.2.06.01'));
        $this->assertFalse(StandarKodefikasi::Simbada->cocok('ALAT-KESEHATAN'));
    }

    public function test_impor_menolak_kode_yang_tidak_sesuai_pola(): void
    {
        $this->actingAs($this->buatPengguna(['Aset.Lihat', 'Aset.Ubah']))
            ->post('/kodefikasi/katalog/impor', [
                'Standar' => 'SimakBmn',
                'Berkas' => $this->berkas(
                    "Kode,Uraian\n3.05.01.04.001,Tempat Tidur Pasien\nBUKAN-KODE,Ngawur\n",
                ),
            ])
            ->assertSessionDoesntHaveErrors();

        app(KonteksOrganisasi::class)->tetapkan($this->organisasi->Id);
        $this->assertSame(1, KodeBarang::query()->count());
        $this->assertSame('3.05.01.04.001', KodeBarang::query()->value('Kode'));
    }

    public function test_impor_ulang_memperbarui_bukan_menggandakan(): void
    {
        $pengguna = $this->buatPengguna(['Aset.Lihat', 'Aset.Ubah']);

        foreach (['Tempat Tidur', 'Tempat Tidur Pasien'] as $uraian) {
            $this->actingAs($pengguna)->post('/kodefikasi/katalog/impor', [
                'Standar' => 'SimakBmn',
                'Berkas' => $this->berkas("Kode,Uraian\n3.05.01.04.001,{$uraian}\n"),
            ]);
        }

        app(KonteksOrganisasi::class)->tetapkan($this->organisasi->Id);
        $this->assertSame(1, KodeBarang::query()->count());
        $this->assertSame('Tempat Tidur Pasien', KodeBarang::query()->value('Uraian'));
    }

    /** NUP berurut per kode barang: dua alat sejenis bernomor 1 dan 2. */
    public function test_nup_berurut_per_kode_barang(): void
    {
        $kode = $this->buatKode('3.05.01.04.001', 'Tempat Tidur');
        $aksi = app(TetapkanKodeBarang::class);

        $pertama = $aksi->jalankan($this->buatAset('Tempat Tidur A'), $kode);
        $kedua = $aksi->jalankan($this->buatAset('Tempat Tidur B'), $kode);

        $this->assertSame(1, $pertama->Nup);
        $this->assertSame(2, $kedua->Nup);
    }

    /** Penghitung dipisah per kode: kode lain memulai dari satu lagi. */
    public function test_nup_tidak_berbagi_penghitung_antar_kode_barang(): void
    {
        $aksi = app(TetapkanKodeBarang::class);

        $aksi->jalankan($this->buatAset('Tempat Tidur'), $this->buatKode('3.05.01.04.001', 'Tempat Tidur'));
        $keduaJenis = $aksi->jalankan($this->buatAset('Kursi Roda'), $this->buatKode('3.05.01.05.002', 'Kursi Roda'));

        $this->assertSame(1, $keduaJenis->Nup);
    }

    /** Menetapkan kode yang sama dua kali tidak boleh menggeser nomor yang sudah dilaporkan. */
    public function test_penetapan_ulang_kode_yang_sama_mempertahankan_nup(): void
    {
        $kode = $this->buatKode('3.05.01.04.001', 'Tempat Tidur');
        $aset = $this->buatAset('Tempat Tidur');
        $aksi = app(TetapkanKodeBarang::class);

        $pertama = $aksi->jalankan($aset, $kode);
        $kedua = $aksi->jalankan($aset, $kode);

        $this->assertSame($pertama->Nup, $kedua->Nup);
        $this->assertSame(1, KodeBarangAset::query()->count());
    }

    /** Pindah kode berarti NUP baru; nomor lama tidak berlaku di bawah kode berbeda. */
    public function test_mengganti_kode_menerbitkan_nup_baru_dan_tidak_menggandakan_baris(): void
    {
        $aset = $this->buatAset('Alat');
        $aksi = app(TetapkanKodeBarang::class);

        $aksi->jalankan($aset, $this->buatKode('3.05.01.04.001', 'Tempat Tidur'));
        $lain = $this->buatKode('3.05.01.05.002', 'Kursi Roda');
        $sesudah = $aksi->jalankan($aset, $lain);

        $this->assertSame($lain->Id, $sesudah->KodeBarangId);
        $this->assertSame(1, KodeBarangAset::query()->where('AsetId', $aset->Id)->count());
    }

    /** Satu aset boleh punya kode BMN dan BMD sekaligus; keduanya standar berbeda. */
    public function test_satu_aset_dapat_punya_kode_kedua_standar(): void
    {
        $aset = $this->buatAset('Alat');
        $aksi = app(TetapkanKodeBarang::class);

        $aksi->jalankan($aset, $this->buatKode('3.05.01.04.001', 'Tempat Tidur'));
        $aksi->jalankan($aset, $this->buatKode('1.3.2.06.01.01.001', 'Tempat Tidur', StandarKodefikasi::Simbada));

        $this->assertSame(2, KodeBarangAset::query()->where('AsetId', $aset->Id)->count());
    }

    /**
     * Kode registrasi = kode lokasi (18) + tahun (4) + kode barang (10) + NUP (6).
     */
    public function test_kode_registrasi_disusun_lengkap(): void
    {
        KonfigurasiOrganisasi::create([
            'Kunci' => PenyusunKodeRegistrasi::KUNCI_KODE_LOKASI,
            'Nilai' => '024015400012345678',
        ]);

        $aset = $this->buatAset('Tempat Tidur', tanggalPerolehan: '2023-05-10');
        $penetapan = app(TetapkanKodeBarang::class)
            ->jalankan($aset, $this->buatKode('3.05.01.04.001', 'Tempat Tidur'));

        $kode = app(PenyusunKodeRegistrasi::class)->untuk(
            $penetapan->fresh(['kodeBarang', 'aset'])
        );

        $this->assertSame('024015400012345678.2023.3.05.01.04.001.000001', $kode);
    }

    /** Nomor registrasi yang salah lebih berbahaya daripada kolom kosong. */
    public function test_kode_registrasi_tidak_diterbitkan_tanpa_kode_lokasi(): void
    {
        $aset = $this->buatAset('Tempat Tidur', tanggalPerolehan: '2023-05-10');
        $penetapan = app(TetapkanKodeBarang::class)
            ->jalankan($aset, $this->buatKode('3.05.01.04.001', 'Tempat Tidur'));

        $penyusun = app(PenyusunKodeRegistrasi::class);
        $segar = $penetapan->fresh(['kodeBarang', 'aset']);

        $this->assertNull($penyusun->untuk($segar));
        $this->assertStringContainsString('Kode lokasi', (string) $penyusun->alasanBelumLengkap($segar));
    }

    public function test_kode_lokasi_yang_panjangnya_salah_ditolak(): void
    {
        KonfigurasiOrganisasi::create([
            'Kunci' => PenyusunKodeRegistrasi::KUNCI_KODE_LOKASI,
            'Nilai' => '12345',
        ]);

        $aset = $this->buatAset('Tempat Tidur', tanggalPerolehan: '2023-05-10');
        $penetapan = app(TetapkanKodeBarang::class)
            ->jalankan($aset, $this->buatKode('3.05.01.04.001', 'Tempat Tidur'));

        $this->assertNull(app(PenyusunKodeRegistrasi::class)->untuk($penetapan->fresh(['kodeBarang', 'aset'])));
    }

    /** Kode registrasi hanya dikenal barang milik negara. */
    public function test_simbada_tidak_menerbitkan_kode_registrasi_bmn(): void
    {
        KonfigurasiOrganisasi::create([
            'Kunci' => PenyusunKodeRegistrasi::KUNCI_KODE_LOKASI,
            'Nilai' => '024015400012345678',
        ]);

        $aset = $this->buatAset('Alat', tanggalPerolehan: '2023-05-10');
        $penetapan = app(TetapkanKodeBarang::class)->jalankan(
            $aset,
            $this->buatKode('1.3.2.06.01.01.001', 'Alat', StandarKodefikasi::Simbada),
        );

        $this->assertNull(app(PenyusunKodeRegistrasi::class)->untuk($penetapan->fresh(['kodeBarang', 'aset'])));
    }

    public function test_ekspor_memuat_nup_dan_kode_registrasi(): void
    {
        KonfigurasiOrganisasi::create([
            'Kunci' => PenyusunKodeRegistrasi::KUNCI_KODE_LOKASI,
            'Nilai' => '024015400012345678',
        ]);

        $aset = $this->buatAset('Tempat Tidur', tanggalPerolehan: '2023-05-10');
        app(TetapkanKodeBarang::class)->jalankan($aset, $this->buatKode('3.05.01.04.001', 'Tempat Tidur'));

        $respons = $this->actingAs($this->buatPengguna(['Aset.Lihat']))->get('/kodefikasi/ekspor?standar=SimakBmn');
        $respons->assertOk();
        $isi = $respons->streamedContent();

        $this->assertStringContainsString('000001', $isi);
        $this->assertStringContainsString('024015400012345678.2023.3.05.01.04.001.000001', $isi);
    }

    public function test_menetapkan_kode_butuh_izin_ubah_aset(): void
    {
        $aset = $this->buatAset('Alat');
        $kode = $this->buatKode('3.05.01.04.001', 'Tempat Tidur');

        $this->actingAs($this->buatPengguna(['Aset.Lihat']))
            ->post('/kodefikasi/penetapan', ['AsetId' => $aset->Id, 'KodeBarangId' => $kode->Id])
            ->assertForbidden();
    }

    /** Katalog satu rumah sakit tidak boleh terlihat rumah sakit lain. */
    public function test_katalog_tidak_bocor_lintas_organisasi(): void
    {
        $this->buatKode('3.05.01.04.001', 'Milik RS Kita');

        $lain = Organisasi::create(['Kode' => 'ORG-LAIN', 'Nama' => 'RS Lain', 'Status' => 'Aktif']);
        app(KonteksOrganisasi::class)->tetapkan($lain->Id);

        $this->assertSame(0, KodeBarang::query()->count());
    }

    private function buatKode(
        string $kode,
        string $uraian,
        StandarKodefikasi $standar = StandarKodefikasi::SimakBmn,
    ): KodeBarang {
        return KodeBarang::create([
            'Standar' => $standar->value,
            'Kode' => $kode,
            'Uraian' => $uraian,
            'Aktif' => true,
        ]);
    }

    private function buatAset(string $nama, ?string $tanggalPerolehan = null): Aset
    {
        return Aset::create([
            'KategoriAsetId' => $this->kategori->Id,
            'Nama' => $nama,
            'Status' => 'Aktif',
            'Kondisi' => 'Baik',
            'TanggalPerolehan' => $tanggalPerolehan,
        ]);
    }

    private function berkas(string $isi): UploadedFile
    {
        $jalur = tempnam(sys_get_temp_dir(), 'kdf').'.csv';
        file_put_contents($jalur, $isi);

        return new UploadedFile($jalur, 'katalog.csv', 'text/csv', null, true);
    }

    /** @param  list<string>  $kodeIzin */
    private function buatPengguna(array $kodeIzin): Pengguna
    {
        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($this->organisasi->Id);

        $pengguna = Pengguna::create([
            'OrganisasiId' => $this->organisasi->Id,
            'Nama' => 'Petugas',
            'Email' => 'petugas+'.uniqid().'@amanpoll.test',
            'KataSandi' => 'rahasia',
            'Status' => 'Aktif',
        ]);

        $peran = Peran::create(['Kode' => 'PRN-'.uniqid(), 'Nama' => 'Petugas']);

        foreach ($kodeIzin as $kode) {
            $izin = Izin::firstOrCreate(['Kode' => $kode], ['Nama' => $kode, 'Modul' => 'Uji']);
            PeranIzin::create(['PeranId' => $peran->Id, 'IzinId' => $izin->Id]);
        }

        PenggunaPeran::create(['PenggunaId' => $pengguna->Id, 'PeranId' => $peran->Id]);

        return $pengguna;
    }
}
