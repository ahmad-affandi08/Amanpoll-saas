<?php

declare(strict_types=1);

namespace Tests\Feature\Aspak;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Aset\Infrastructure\Persistence\Models\KategoriAset;
use App\Domain\Aset\Infrastructure\Persistence\Models\Merek;
use App\Domain\Aset\Infrastructure\Persistence\Models\ModelAset;
use App\Domain\Aspak\Application\Services\PenyusunBarisAspak;
use App\Domain\Aspak\Infrastructure\Persistence\Models\AlkesAspak;
use App\Domain\Aspak\Infrastructure\Persistence\Models\PemetaanAspak;
use App\Domain\Platform\Infrastructure\Persistence\Models\Izin;
use App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenggunaPeran;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Domain\Platform\Infrastructure\Persistence\Models\PeranIzin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class AspakTest extends TestCase
{
    use RefreshDatabase;

    private Organisasi $organisasi;

    private KategoriAset $kategori;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organisasi = Organisasi::create(['Kode' => 'ORG-ASPAK', 'Nama' => 'RS Uji', 'Status' => 'Aktif']);
        app(KonteksOrganisasi::class)->tetapkan($this->organisasi->Id);

        $this->kategori = KategoriAset::create(['Nama' => 'Alat Medis']);
    }

    public function test_impor_katalog_mengisi_alkes_dari_csv(): void
    {
        $this->actingAs($this->buatAdmin())
            ->post('/aspak/katalog/impor', ['Berkas' => $this->berkas(
                "Kode,Nama,Kelompok,Satuan\nALK-001,Tensimeter,Diagnostik,Unit\nALK-002,Nebulizer,Terapi,Unit\n",
            )])
            ->assertSessionDoesntHaveErrors();

        $this->pulihkanKonteks();
        $this->assertSame(2, AlkesAspak::query()->count());
        $this->assertSame('Tensimeter', AlkesAspak::query()->where('Kode', 'ALK-001')->value('Nama'));
    }

    /** Katalog ASPAK diunduh ulang tiap kali nomenklaturnya berubah. */
    public function test_impor_ulang_memperbarui_bukan_menggandakan(): void
    {
        $admin = $this->buatAdmin();

        $this->actingAs($admin)->post('/aspak/katalog/impor', [
            'Berkas' => $this->berkas("Kode,Nama\nALK-001,Tensimeter\n"),
        ]);
        $this->actingAs($admin)->post('/aspak/katalog/impor', [
            'Berkas' => $this->berkas("Kode,Nama\nALK-001,Tensimeter Digital\n"),
        ]);

        $this->pulihkanKonteks();
        $this->assertSame(1, AlkesAspak::query()->count());
        $this->assertSame('Tensimeter Digital', AlkesAspak::query()->where('Kode', 'ALK-001')->value('Nama'));
    }

    public function test_baris_tanpa_kode_atau_nama_dilewati(): void
    {
        $this->actingAs($this->buatAdmin())->post('/aspak/katalog/impor', [
            'Berkas' => $this->berkas("Kode,Nama\nALK-001,Tensimeter\n,Tanpa Kode\nALK-003,\n"),
        ]);

        $this->pulihkanKonteks();
        $this->assertSame(1, AlkesAspak::query()->count());
    }

    /** Judul kolom katalog tidak seragam antar unduhan ASPAK. */
    public function test_judul_kolom_alternatif_dikenali(): void
    {
        $this->actingAs($this->buatAdmin())->post('/aspak/katalog/impor', [
            'Berkas' => $this->berkas("Kode Alat,Nama Alat\nALK-009,EKG\n"),
        ]);

        $this->pulihkanKonteks();
        $this->assertSame('EKG', AlkesAspak::query()->where('Kode', 'ALK-009')->value('Nama'));
    }

    public function test_ekspor_memuat_aset_yang_dipetakan_lewat_kategori(): void
    {
        $alkes = AlkesAspak::create(['Kode' => 'ALK-001', 'Nama' => 'Tensimeter']);
        PemetaanAspak::create(['AlkesAspakId' => $alkes->Id, 'KategoriAsetId' => $this->kategori->Id]);

        $lokasi = Lokasi::create(['Kode' => 'LOK-1', 'Nama' => 'Poli Umum', 'KodeRuangAspak' => 'R-001']);
        $this->buatAset('Tensi Poli', lokasi: $lokasi);

        $isi = $this->unduhEkspor();

        $this->assertStringContainsString('ALK-001', $isi);
        $this->assertStringContainsString('R-001', $isi);
        $this->assertStringContainsString('Poli Umum', $isi);
    }

    /**
     * ASPAK menolak baris tanpa kode alat, jadi aset tak terpetakan tidak dikirim.
     *
     * Dihitung barisnya, bukan dicari namanya: nama aset tidak termasuk kolom
     * ekspor bawaan, sehingga mencarinya akan selalu lolos apa pun yang terjadi.
     */
    public function test_aset_tanpa_pemetaan_tidak_ikut_diekspor(): void
    {
        $alkes = AlkesAspak::create(['Kode' => 'ALK-001', 'Nama' => 'Tensimeter']);
        PemetaanAspak::create(['AlkesAspakId' => $alkes->Id, 'KategoriAsetId' => $this->kategori->Id]);

        $this->buatAset('Aset Terpetakan');

        $lain = KategoriAset::create(['Nama' => 'Kategori Tanpa Pemetaan']);
        Aset::create([
            'KategoriAsetId' => $lain->Id,
            'Nama' => 'Aset Yatim',
            'Status' => 'Aktif',
            'Kondisi' => 'Baik',
        ]);

        $this->assertSame(1, $this->jumlahBarisData($this->unduhEkspor()));
    }

    public function test_pemetaan_model_menang_atas_pemetaan_kategori(): void
    {
        $lewatKategori = AlkesAspak::create(['Kode' => 'ALK-KAT', 'Nama' => 'Lewat Kategori']);
        $lewatModel = AlkesAspak::create(['Kode' => 'ALK-MOD', 'Nama' => 'Lewat Model']);

        $merek = Merek::create(['Nama' => 'Omron']);
        $model = ModelAset::create([
            'KategoriAsetId' => $this->kategori->Id,
            'MerekId' => $merek->Id,
            'Nama' => 'HEM-7120',
        ]);

        PemetaanAspak::create(['AlkesAspakId' => $lewatKategori->Id, 'KategoriAsetId' => $this->kategori->Id]);
        PemetaanAspak::create(['AlkesAspakId' => $lewatModel->Id, 'ModelAsetId' => $model->Id]);

        $aset = $this->buatAset('Tensi Omron', model: $model);
        $baris = app(PenyusunBarisAspak::class)->untuk($aset->fresh(['lokasi', 'modelAset.merek', 'pelaksanaanKalibrasi']));

        $this->assertSame('ALK-MOD', $baris['KodeAlkes']);
        $this->assertSame('Omron', $baris['Merek']);
        $this->assertSame('HEM-7120', $baris['Tipe']);
    }

    /**
     * LokasiId dan ModelAsetId boleh kosong. Membaca relasinya sebagai properti
     * memicu peringatan PHP, yang kini menggagalkan test karena failOnWarning.
     */
    public function test_aset_tanpa_lokasi_dan_model_menghasilkan_sel_kosong(): void
    {
        $alkes = AlkesAspak::create(['Kode' => 'ALK-001', 'Nama' => 'Tensimeter']);
        PemetaanAspak::create(['AlkesAspakId' => $alkes->Id, 'KategoriAsetId' => $this->kategori->Id]);

        $aset = $this->buatAset('Tanpa Lokasi');
        $baris = app(PenyusunBarisAspak::class)->untuk($aset->fresh(['lokasi', 'modelAset.merek', 'pelaksanaanKalibrasi']));

        $this->assertSame('', $baris['KodeRuang']);
        $this->assertSame('', $baris['NamaRuang']);
        $this->assertSame('', $baris['Merek']);
        $this->assertSame('', $baris['Tipe']);
    }

    /** Rusak dilaporkan rusak berat; menyebutnya ringan menggelembungkan kesiapan alat. */
    public function test_kondisi_dipetakan_ke_istilah_aspak(): void
    {
        $alkes = AlkesAspak::create(['Kode' => 'ALK-001', 'Nama' => 'Tensimeter']);
        PemetaanAspak::create(['AlkesAspakId' => $alkes->Id, 'KategoriAsetId' => $this->kategori->Id]);

        $penyusun = app(PenyusunBarisAspak::class);

        foreach ([['Baik', 'Baik'], ['PerluPerhatian', 'Rusak Ringan'], ['Rusak', 'Rusak Berat']] as [$kita, $aspak]) {
            $aset = $this->buatAset('Aset '.$kita, kondisi: $kita);
            $baris = $penyusun->untuk($aset->fresh(['lokasi', 'modelAset.merek', 'pelaksanaanKalibrasi']));

            $this->assertSame($aspak, $baris['Kondisi']);
        }
    }

    /**
     * Nama alkes berasal dari berkas katalog yang diunduh dari luar, dan nama
     * ruang diketik orang. Keduanya masuk berkas ekspor, jadi sel yang diawali
     * karakter rumus harus dinetralkan sebelum dibuka di Excel.
     */
    public function test_ekspor_menetralkan_sel_yang_diawali_rumus(): void
    {
        $alkes = AlkesAspak::create(['Kode' => 'ALK-001', 'Nama' => '=HYPERLINK("http://jahat.test")']);
        PemetaanAspak::create(['AlkesAspakId' => $alkes->Id, 'KategoriAsetId' => $this->kategori->Id]);

        $lokasi = Lokasi::create(['Kode' => 'LOK-X', 'Nama' => '+SUM(A1)', 'KodeRuangAspak' => 'R-002']);
        $this->buatAset('Tensi', lokasi: $lokasi);

        $isi = $this->unduhEkspor();

        $this->assertStringContainsString("'=HYPERLINK", $isi);
        $this->assertStringContainsString("'+SUM(A1)", $isi);
    }

    public function test_pengguna_tanpa_izin_ditolak(): void
    {
        $pengguna = Pengguna::create([
            'OrganisasiId' => $this->organisasi->Id,
            'Nama' => 'Biasa',
            'Email' => 'biasa+'.uniqid().'@amanpoll.test',
            'KataSandi' => 'rahasia',
            'Status' => 'Aktif',
        ]);

        $this->actingAs($pengguna)->get('/aspak')->assertForbidden();
        $this->actingAs($pengguna)->get('/aspak/ekspor')->assertForbidden();
    }

    /** Katalog satu rumah sakit tidak boleh bocor ke rumah sakit lain. */
    public function test_katalog_tidak_bocor_lintas_organisasi(): void
    {
        AlkesAspak::create(['Kode' => 'ALK-RAHASIA', 'Nama' => 'Milik RS A']);

        $lain = Organisasi::create(['Kode' => 'ORG-LAIN', 'Nama' => 'RS B', 'Status' => 'Aktif']);
        app(KonteksOrganisasi::class)->tetapkan($lain->Id);

        $this->assertSame(0, AlkesAspak::query()->count());
    }

    /** Baris isi berkas CSV, tanpa baris kepala kolom. */
    private function jumlahBarisData(string $csv): int
    {
        $baris = array_filter(explode("\n", trim($csv)), static fn (string $satu): bool => trim($satu) !== '');

        return max(0, count($baris) - 1);
    }

    /** Middleware membersihkan konteks organisasi sesudah tiap request. */
    private function pulihkanKonteks(): void
    {
        app(KonteksOrganisasi::class)->tetapkan($this->organisasi->Id);
    }

    private function unduhEkspor(): string
    {
        $respons = $this->actingAs($this->buatAdmin())->get('/aspak/ekspor');
        $respons->assertOk();

        return $respons->streamedContent();
    }

    private function buatAset(
        string $nama,
        ?Lokasi $lokasi = null,
        ?ModelAset $model = null,
        string $kondisi = 'Baik',
    ): Aset {
        return Aset::create([
            'KategoriAsetId' => $this->kategori->Id,
            'LokasiId' => $lokasi?->Id,
            'ModelAsetId' => $model?->Id,
            'Nama' => $nama,
            'Status' => 'Aktif',
            'Kondisi' => $kondisi,
        ]);
    }

    private function berkas(string $isi): UploadedFile
    {
        $jalur = tempnam(sys_get_temp_dir(), 'aspak').'.csv';
        file_put_contents($jalur, $isi);

        return new UploadedFile($jalur, 'katalog.csv', 'text/csv', null, true);
    }

    private function buatAdmin(): Pengguna
    {
        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($this->organisasi->Id);

        $pengguna = Pengguna::create([
            'OrganisasiId' => $this->organisasi->Id,
            'Nama' => 'Admin',
            'Email' => 'admin+'.uniqid().'@amanpoll.test',
            'KataSandi' => 'rahasia',
            'Status' => 'Aktif',
        ]);

        $peran = Peran::query()->where('Kode', 'ADMIN-ASPAK')->first()
            ?? Peran::create(['Kode' => 'ADMIN-ASPAK', 'Nama' => 'Admin ASPAK']);

        $izin = Izin::firstOrCreate(['Kode' => 'Aspak.Kelola'], ['Nama' => 'Kelola ASPAK', 'Modul' => 'Aspak']);
        PeranIzin::firstOrCreate(['PeranId' => $peran->Id, 'IzinId' => $izin->Id]);
        PenggunaPeran::create(['PenggunaId' => $pengguna->Id, 'PeranId' => $peran->Id]);

        return $pengguna;
    }
}
