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
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenggunaPeran;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Domain\Platform\Infrastructure\Persistence\Models\PeranIzin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Nomenklatur standar Kemenkes pada aset.
 *
 * Katalog ASPAK adalah nomenklatur itu sendiri; yang ditambahkan di sini
 * adalah memakainya saat aset didaftarkan, bukan hanya saat diekspor.
 */
class NomenklaturAsetTest extends TestCase
{
    use RefreshDatabase;

    private Organisasi $organisasi;

    private KategoriAset $kategori;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organisasi = Organisasi::create(['Kode' => 'ORG-NOM', 'Nama' => 'RS Uji', 'Status' => 'Aktif']);
        app(KonteksOrganisasi::class)->tetapkan($this->organisasi->Id);

        $this->kategori = KategoriAset::create(['Nama' => 'Alat Medis']);
    }

    /** Nomenklatur di aset lebih khusus daripada pemetaan model maupun kategori. */
    public function test_nomenklatur_aset_menang_atas_pemetaan_model_dan_kategori(): void
    {
        $lewatKategori = AlkesAspak::create(['Kode' => 'ALK-KAT', 'Nama' => 'Lewat Kategori']);
        $lewatModel = AlkesAspak::create(['Kode' => 'ALK-MOD', 'Nama' => 'Lewat Model']);
        $sendiri = AlkesAspak::create(['Kode' => 'ALK-ASET', 'Nama' => 'Dipilih di Aset']);

        $merek = Merek::create(['Nama' => 'Omron']);
        $model = ModelAset::create([
            'KategoriAsetId' => $this->kategori->Id,
            'MerekId' => $merek->Id,
            'Nama' => 'HEM-7120',
        ]);

        PemetaanAspak::create(['AlkesAspakId' => $lewatKategori->Id, 'KategoriAsetId' => $this->kategori->Id]);
        PemetaanAspak::create(['AlkesAspakId' => $lewatModel->Id, 'ModelAsetId' => $model->Id]);

        $aset = $this->buatAset('Tensi', model: $model, alkes: $sendiri);
        $baris = app(PenyusunBarisAspak::class)->untuk($this->segar($aset));

        $this->assertSame('ALK-ASET', $baris['KodeAlkes']);
        $this->assertSame('Dipilih di Aset', $baris['NamaAlkes']);
    }

    /** Tanpa nomenklatur sendiri, urutan lama tetap berlaku. */
    public function test_tanpa_nomenklatur_sendiri_pemetaan_model_tetap_dipakai(): void
    {
        $lewatModel = AlkesAspak::create(['Kode' => 'ALK-MOD', 'Nama' => 'Lewat Model']);
        $model = ModelAset::create(['KategoriAsetId' => $this->kategori->Id, 'Nama' => 'HEM-7120']);
        PemetaanAspak::create(['AlkesAspakId' => $lewatModel->Id, 'ModelAsetId' => $model->Id]);

        $aset = $this->buatAset('Tensi', model: $model);
        $baris = app(PenyusunBarisAspak::class)->untuk($this->segar($aset));

        $this->assertSame('ALK-MOD', $baris['KodeAlkes']);
    }

    /** Aset tanpa kategori atau model terpetakan tetap dapat diekspor lewat nomenklaturnya sendiri. */
    public function test_aset_tanpa_pemetaan_apa_pun_ikut_diekspor_lewat_nomenklaturnya(): void
    {
        $alkes = AlkesAspak::create(['Kode' => 'ALK-001', 'Nama' => 'Tensimeter']);
        $this->buatAset('Tensi Lepas', alkes: $alkes);

        $respons = $this->actingAs($this->buatPengguna(['Aspak.Kelola', 'Aset.Lihat']))->get('/aspak/ekspor');
        $respons->assertOk();

        $this->assertStringContainsString('ALK-001', $respons->streamedContent());
    }

    public function test_nomenklatur_dapat_disimpan_lewat_formulir_aset(): void
    {
        $alkes = AlkesAspak::create(['Kode' => 'ALK-001', 'Nama' => 'Tensimeter']);
        $pengguna = $this->buatPengguna(['Aset.Lihat', 'Aset.Buat']);

        $this->actingAs($pengguna)->post('/aset', [
            'Nama' => 'Tensi Poli',
            'KategoriAsetId' => $this->kategori->Id,
            'AlkesAspakId' => $alkes->Id,
            'Status' => 'Aktif',
            'Kondisi' => 'Baik',
            'TingkatKritis' => 'Normal',
        ])->assertSessionDoesntHaveErrors();

        app(KonteksOrganisasi::class)->tetapkan($this->organisasi->Id);
        $this->assertDatabaseHas('Aset', ['Nama' => 'Tensi Poli', 'AlkesAspakId' => $alkes->Id]);
    }

    /** Nomenklatur milik organisasi lain tidak boleh dapat dipasang. */
    public function test_nomenklatur_organisasi_lain_ditolak(): void
    {
        $lain = Organisasi::create(['Kode' => 'ORG-LAIN', 'Nama' => 'RS Lain', 'Status' => 'Aktif']);
        app(KonteksOrganisasi::class)->tetapkan($lain->Id);
        $milikOrang = AlkesAspak::create(['Kode' => 'ALK-X', 'Nama' => 'Milik RS Lain']);

        app(KonteksOrganisasi::class)->tetapkan($this->organisasi->Id);
        $pengguna = $this->buatPengguna(['Aset.Lihat', 'Aset.Buat']);

        $this->actingAs($pengguna)->post('/aset', [
            'Nama' => 'Tensi Selundupan',
            'KategoriAsetId' => $this->kategori->Id,
            'AlkesAspakId' => $milikOrang->Id,
            'Status' => 'Aktif',
            'Kondisi' => 'Baik',
            'TingkatKritis' => 'Normal',
        ])->assertSessionHasErrors('AlkesAspakId');
    }

    public function test_pencarian_katalog_menyaring_menurut_kode_dan_nama(): void
    {
        AlkesAspak::create(['Kode' => 'ALK-001', 'Nama' => 'Tensimeter Digital']);
        AlkesAspak::create(['Kode' => 'ALK-002', 'Nama' => 'Nebulizer']);

        $pengguna = $this->buatPengguna(['Aset.Lihat']);

        $this->actingAs($pengguna)->getJson('/aspak/katalog/cari?q=tensi')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonFragment(['Kode' => 'ALK-001']);

        $this->actingAs($pengguna)->getJson('/aspak/katalog/cari?q=ALK-002')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonFragment(['Nama' => 'Nebulizer']);
    }

    /** Katalog dicari oleh petugas yang mendaftarkan aset, bukan hanya pengelola ASPAK. */
    public function test_pencarian_katalog_cukup_dengan_izin_lihat_aset(): void
    {
        AlkesAspak::create(['Kode' => 'ALK-001', 'Nama' => 'Tensimeter']);

        $this->actingAs($this->buatPengguna(['Aset.Lihat']))
            ->getJson('/aspak/katalog/cari')
            ->assertOk()
            ->assertJsonCount(1);
    }

    public function test_pencarian_katalog_ditolak_tanpa_izin_aset(): void
    {
        $pengguna = Pengguna::create([
            'OrganisasiId' => $this->organisasi->Id,
            'Nama' => 'Biasa',
            'Email' => 'biasa+'.uniqid().'@amanpoll.test',
            'KataSandi' => 'rahasia',
            'Status' => 'Aktif',
        ]);

        $this->actingAs($pengguna)->getJson('/aspak/katalog/cari')->assertForbidden();
    }

    /** Katalog satu rumah sakit tidak boleh muncul di pencarian rumah sakit lain. */
    public function test_pencarian_katalog_tidak_bocor_lintas_organisasi(): void
    {
        AlkesAspak::create(['Kode' => 'ALK-RAHASIA', 'Nama' => 'Milik RS Kita']);

        $lain = Organisasi::create(['Kode' => 'ORG-LAIN', 'Nama' => 'RS Lain', 'Status' => 'Aktif']);
        app(KonteksOrganisasi::class)->tetapkan($lain->Id);
        KategoriAset::create(['Nama' => 'Alat Medis']);
        $orangLain = $this->buatPengguna(['Aset.Lihat'], $lain);

        $this->actingAs($orangLain)->getJson('/aspak/katalog/cari')
            ->assertOk()
            ->assertJsonCount(0);
    }

    /** Angka kesiapan ekspor harus menghitung aset yang bernomenklatur sendiri. */
    public function test_ringkasan_menghitung_aset_bernomenklatur_sendiri(): void
    {
        $alkes = AlkesAspak::create(['Kode' => 'ALK-001', 'Nama' => 'Tensimeter']);
        $this->buatAset('Tensi', alkes: $alkes);
        $this->buatAset('Aset Yatim');

        $this->actingAs($this->buatPengguna(['Aspak.Kelola', 'Aset.Lihat']))->get('/aspak')
            ->assertOk()
            ->assertInertia(fn ($props) => $props
                ->where('ringkasan.AsetTerpetakan', 1)
                ->where('ringkasan.AsetBelumTerpetakan', 1)
                ->etc());
    }

    private function segar(Aset $aset): Aset
    {
        return $aset->fresh(['lokasi', 'modelAset.merek', 'alkesAspak', 'pelaksanaanKalibrasi']);
    }

    private function buatAset(
        string $nama,
        ?ModelAset $model = null,
        ?AlkesAspak $alkes = null,
    ): Aset {
        return Aset::create([
            'KategoriAsetId' => $this->kategori->Id,
            'ModelAsetId' => $model?->Id,
            'AlkesAspakId' => $alkes?->Id,
            'Nama' => $nama,
            'Status' => 'Aktif',
            'Kondisi' => 'Baik',
            'TingkatKritis' => 'Normal',
        ]);
    }

    /** @param  list<string>  $kodeIzin */
    private function buatPengguna(array $kodeIzin, ?Organisasi $organisasi = null): Pengguna
    {
        $organisasi ??= $this->organisasi;
        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);

        $pengguna = Pengguna::create([
            'OrganisasiId' => $organisasi->Id,
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
