<?php

declare(strict_types=1);

namespace Tests\Feature\Aset;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Aset\Application\Services\PenghitungKelayakanAset;
use App\Domain\Aset\Domain\ValueObjects\ParameterKelayakan;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Aset\Infrastructure\Persistence\Models\KategoriAset;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\BiayaPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerjaAset;
use App\Domain\Platform\Infrastructure\Persistence\Models\Izin;
use App\Domain\Platform\Infrastructure\Persistence\Models\KonfigurasiOrganisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenggunaPeran;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Domain\Platform\Infrastructure\Persistence\Models\PeranIzin;
use Carbon\CarbonImmutable;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * AIC dan MMEL.
 *
 *   AIC  = IIC x (1 + i)^t / L
 *   MMEL = FaktorMel x (sisa usia manfaat / usia teknis) x harga perkiraan pengganti
 *
 * Angka di test ini dipilih supaya dapat dihitung tangan, sehingga yang diuji
 * rumusnya, bukan sekadar bahwa ada angka yang keluar.
 */
class KelayakanAsetTest extends TestCase
{
    use RefreshDatabase;

    private Organisasi $organisasi;

    private KategoriAset $kategori;

    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow('2026-01-01 00:00:00');

        $this->organisasi = Organisasi::create(['Kode' => 'ORG-KLY', 'Nama' => 'RS Uji', 'Status' => 'Aktif']);
        app(KonteksOrganisasi::class)->tetapkan($this->organisasi->Id);

        $this->kategori = KategoriAset::create(['Nama' => 'Alat Medis']);
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    /**
     * Aset 100 juta, umur teknis 10 tahun, dipakai tepat 4 tahun, inflasi
     * bawaan 5%, faktor MEL bawaan 0,6.
     *
     *   pengganti = 100jt x 1,05^4            = 121.550.625
     *   AIC       = 121.550.625 / 10          =  12.155.062
     *   sisa      = 10 - 4 = 6 -> 60%
     *   MMEL      = 0,6 x 0,6 x 121.550.625   =  43.758.225
     */
    public function test_aic_dan_mmel_sesuai_rumus(): void
    {
        $aset = $this->buatAset(harga: 100_000_000, umurBulan: 120, mulai: '2022-01-01');

        $angka = app(PenghitungKelayakanAset::class)->untuk(
            $aset,
            ParameterKelayakan::bawaan(),
        );

        $this->assertEqualsWithDelta(121_550_625, $angka['HargaPerkiraanPengganti'], 2_000);
        $this->assertEqualsWithDelta(12_155_062, $angka['Aic'], 200);
        $this->assertEqualsWithDelta(0.6, $angka['PersentaseUsiaManfaat'], 0.01);
        $this->assertEqualsWithDelta(43_758_225, $angka['Mmel'], 2_000);
    }

    /** Sifat yang membedakan AIC dari harga perolehan: ia membesar seiring usia. */
    public function test_aic_membesar_seiring_usia_pakai(): void
    {
        $penghitung = app(PenghitungKelayakanAset::class);
        $parameter = ParameterKelayakan::bawaan();

        $baru = $penghitung->untuk($this->buatAset(100_000_000, 120, '2025-01-01'), $parameter);
        $tua = $penghitung->untuk($this->buatAset(100_000_000, 120, '2019-01-01'), $parameter);

        $this->assertGreaterThan($baru['Aic'], $tua['Aic']);
    }

    /** Sebaliknya MMEL menyusut: makin tua alatnya, makin kecil biaya yang pantas dikeluarkan. */
    public function test_mmel_menyusut_seiring_usia_pakai(): void
    {
        $penghitung = app(PenghitungKelayakanAset::class);
        $parameter = ParameterKelayakan::bawaan();

        $baru = $penghitung->untuk($this->buatAset(100_000_000, 120, '2025-01-01'), $parameter);
        $tua = $penghitung->untuk($this->buatAset(100_000_000, 120, '2019-01-01'), $parameter);

        $this->assertLessThan($baru['Mmel'], $tua['Mmel']);
    }

    public function test_anggaran_pemeliharaan_adalah_persentase_dari_aic(): void
    {
        $aset = $this->buatAset(100_000_000, 120, '2022-01-01');

        $angka = app(PenghitungKelayakanAset::class)->untuk($aset, ParameterKelayakan::bawaan());

        $this->assertEqualsWithDelta(
            $angka['Aic'] * ParameterKelayakan::BAWAAN_PERSEN_PEMELIHARAAN,
            $angka['AnggaranPemeliharaanTahunan'],
            1,
        );
    }

    /** Biaya perbaikan dijumlahkan lewat PerintahKerjaAset, bukan langsung dari aset. */
    public function test_biaya_perbaikan_kumulatif_dijumlahkan_dari_perintah_kerja(): void
    {
        $aset = $this->buatAset(100_000_000, 120, '2022-01-01');

        $this->catatBiaya($aset, 5_000_000);
        $this->catatBiaya($aset, 3_000_000);

        $angka = app(PenghitungKelayakanAset::class)->untuk($aset, ParameterKelayakan::bawaan());

        $this->assertEqualsWithDelta(8_000_000, $angka['BiayaPerbaikanKumulatif'], 1);
        $this->assertEqualsWithDelta(0.08, $angka['RasioBiayaTerhadapPerolehan'], 0.001);
    }

    public function test_biaya_aset_lain_tidak_ikut_terhitung(): void
    {
        $aset = $this->buatAset(100_000_000, 120, '2022-01-01');
        $lain = $this->buatAset(50_000_000, 120, '2022-01-01');

        $this->catatBiaya($lain, 9_000_000);

        $angka = app(PenghitungKelayakanAset::class)->untuk($aset, ParameterKelayakan::bawaan());

        $this->assertSame(0.0, $angka['BiayaPerbaikanKumulatif']);
    }

    public function test_biaya_melampaui_mmel_disarankan_diganti(): void
    {
        $aset = $this->buatAset(100_000_000, 120, '2022-01-01');
        $this->catatBiaya($aset, 90_000_000);

        $angka = app(PenghitungKelayakanAset::class)->untuk($aset, ParameterKelayakan::bawaan());

        $this->assertFalse($angka['LayakDiperbaiki']);
        $this->assertStringContainsString('melampaui MMEL', $angka['Alasan']);
    }

    /** Usia teknis habis berarti tidak ada biaya perbaikan yang dapat dibenarkan. */
    public function test_usia_teknis_habis_selalu_disarankan_diganti(): void
    {
        $aset = $this->buatAset(100_000_000, 24, '2019-01-01');

        $angka = app(PenghitungKelayakanAset::class)->untuk($aset, ParameterKelayakan::bawaan());

        $this->assertSame(0.0, $angka['Mmel']);
        $this->assertFalse($angka['LayakDiperbaiki']);
        $this->assertStringContainsString('Usia teknis sudah habis', $angka['Alasan']);
    }

    /** Aset yang datanya belum lengkap tidak boleh divonis; angkanya akan mengarang. */
    public function test_aset_tanpa_harga_atau_umur_dinyatakan_belum_dapat_dinilai(): void
    {
        $tanpaHarga = Aset::create([
            'KategoriAsetId' => $this->kategori->Id,
            'Nama' => 'Tanpa Harga',
            'Status' => 'Aktif',
            'Kondisi' => 'Baik',
            'UmurManfaatBulan' => 120,
        ]);

        $angka = app(PenghitungKelayakanAset::class)->untuk($tanpaHarga, ParameterKelayakan::bawaan());

        $this->assertTrue($angka['LayakDiperbaiki']);
        $this->assertStringContainsString('Belum dapat dinilai', $angka['Alasan']);
    }

    public function test_parameter_dibaca_dari_konfigurasi_organisasi(): void
    {
        KonfigurasiOrganisasi::create(['Kunci' => ParameterKelayakan::KUNCI_FAKTOR_MEL, 'Nilai' => '0.8']);
        KonfigurasiOrganisasi::create(['Kunci' => ParameterKelayakan::KUNCI_INFLASI, 'Nilai' => '0.2']);

        $parameter = ParameterKelayakan::dariKonfigurasi();

        $this->assertSame(0.8, $parameter->faktorMel);
        $this->assertSame(0.2, $parameter->lajuInflasi);
        $this->assertSame(ParameterKelayakan::BAWAAN_PERSEN_PEMELIHARAAN, $parameter->persenPemeliharaanAic);
    }

    /** Nilai rusak dikembalikan ke bawaan, bukan menjadi nol yang mematikan perhitungan. */
    public function test_parameter_rusak_kembali_ke_bawaan(): void
    {
        KonfigurasiOrganisasi::create(['Kunci' => ParameterKelayakan::KUNCI_FAKTOR_MEL, 'Nilai' => 'entah']);

        $this->assertSame(ParameterKelayakan::BAWAAN_FAKTOR_MEL, ParameterKelayakan::dariKonfigurasi()->faktorMel);
    }

    public function test_halaman_kelayakan_menampilkan_putusan_per_aset(): void
    {
        $aset = $this->buatAset(100_000_000, 120, '2022-01-01');
        $this->catatBiaya($aset, 90_000_000);

        $this->actingAs($this->buatPengguna())->get('/aset/kelayakan')
            ->assertOk()
            ->assertInertia(fn ($props) => $props
                ->where('aset.data.0.LayakDiperbaiki', false)
                ->where('parameter.FaktorMel', ParameterKelayakan::BAWAAN_FAKTOR_MEL)
                ->etc());
    }

    public function test_endpoint_kelayakan_satu_aset_menolak_tanpa_izin(): void
    {
        $aset = $this->buatAset(100_000_000, 120, '2022-01-01');

        $pengguna = Pengguna::create([
            'OrganisasiId' => $this->organisasi->Id,
            'Nama' => 'Biasa',
            'Email' => 'biasa+'.uniqid().'@amanpoll.test',
            'KataSandi' => 'rahasia',
            'Status' => 'Aktif',
        ]);

        $this->actingAs($pengguna)->getJson('/aset/'.$aset->Id.'/kelayakan')->assertForbidden();
    }

    /**
     * Biaya kumulatif dihitung satu kueri untuk seluruh daftar, bukan per aset.
     *
     * Halaman index memanggil penghitungnya 25 kali dan tidak terganggu, tetapi
     * ekspor tidak punya halaman: satu kueri agregat per aset berarti 8.000
     * kueri berurutan untuk rumah sakit dengan 8.000 aset, dan unduhannya putus
     * di tengah tanpa pesan galat karena headernya sudah telanjur dikirim.
     */
    public function test_biaya_kumulatif_tidak_dikueri_ulang_untuk_setiap_baris_ekspor(): void
    {
        foreach (range(1, 10) as $nomor) {
            $aset = $this->buatAset(100_000_000, 120, '2020-01-01');
            $this->catatBiaya($aset, 1_000_000 * $nomor);
        }

        $agregat = 0;
        DB::listen(function (QueryExecuted $kueri) use (&$agregat): void {
            if (str_contains($kueri->sql, 'BiayaPerintahKerja')) {
                $agregat++;
            }
        });

        $respons = $this->actingAs($this->buatPengguna())->get('/aset/kelayakan/ekspor');
        $respons->assertOk();
        $isi = $respons->streamedContent();

        // Kesepuluh barisnya memang ikut: tanpa ini, nol kueri agregat juga
        // akan "lulus" karena berkasnya kebetulan kosong.
        $this->assertSame(10, substr_count($isi, 'AST-'));
        $this->assertLessThanOrEqual(
            1,
            $agregat,
            "Biaya kumulatif dikueri {$agregat} kali untuk 10 baris; satu subkueri sudah cukup.",
        );
    }

    /**
     * Subkueri dan hitungan per aset harus menghasilkan angka yang sama.
     *
     * Ekspor yang angkanya berbeda dari layar adalah cacat yang tidak akan
     * dilaporkan siapa pun -- hanya dipercaya, lalu dipakai mengusulkan
     * penggantian alat.
     */
    public function test_angka_biaya_di_ekspor_sama_dengan_hitungan_per_aset(): void
    {
        $aset = $this->buatAset(100_000_000, 120, '2020-01-01');
        $this->catatBiaya($aset, 7_250_000);
        $this->catatBiaya($aset, 1_750_000);

        $perAset = app(PenghitungKelayakanAset::class)
            ->untuk($aset->fresh(), ParameterKelayakan::dariKonfigurasi())['BiayaPerbaikanKumulatif'];

        $isi = $this->actingAs($this->buatPengguna())->get('/aset/kelayakan/ekspor')->streamedContent();

        $this->assertSame(9000000.0, $perAset);
        $this->assertStringContainsString('9000000', $isi);
    }

    private function buatAset(float $harga, int $umurBulan, string $mulai): Aset
    {
        return Aset::create([
            'KategoriAsetId' => $this->kategori->Id,
            'Nama' => 'Aset '.uniqid(),
            'Status' => 'Aktif',
            'Kondisi' => 'Baik',
            'HargaPerolehan' => $harga,
            'UmurManfaatBulan' => $umurBulan,
            'TanggalPerolehan' => $mulai,
            'TanggalMulaiOperasi' => $mulai,
        ]);
    }

    private function catatBiaya(Aset $aset, float $jumlah): void
    {
        $perintah = PerintahKerja::create([
            'Nomor' => 'PK-'.uniqid(),
            'Judul' => 'Perbaikan',
            'Jenis' => 'Korektif',
            'Status' => 'Selesai',
            'Prioritas' => 'Normal',
        ]);

        PerintahKerjaAset::create([
            'PerintahKerjaId' => $perintah->Id,
            'AsetId' => $aset->Id,
            'Utama' => true,
        ]);

        BiayaPerintahKerja::create([
            'PerintahKerjaId' => $perintah->Id,
            'JenisBiaya' => 'Sparepart',
            'Jumlah' => $jumlah,
            'MataUang' => 'IDR',
            'TanggalBiaya' => now()->toDateString(),
        ]);
    }

    private function buatPengguna(): Pengguna
    {
        $pengguna = Pengguna::create([
            'OrganisasiId' => $this->organisasi->Id,
            'Nama' => 'Manajer',
            'Email' => 'manajer+'.uniqid().'@amanpoll.test',
            'KataSandi' => 'rahasia',
            'Status' => 'Aktif',
        ]);

        $peran = Peran::create(['Kode' => 'PRN-'.uniqid(), 'Nama' => 'Manajer']);
        $izin = Izin::firstOrCreate(['Kode' => 'Aset.Lihat'], ['Nama' => 'Lihat Aset', 'Modul' => 'Aset']);
        PeranIzin::create(['PeranId' => $peran->Id, 'IzinId' => $izin->Id]);
        PenggunaPeran::create(['PenggunaId' => $pengguna->Id, 'PeranId' => $peran->Id]);

        return $pengguna;
    }
}
