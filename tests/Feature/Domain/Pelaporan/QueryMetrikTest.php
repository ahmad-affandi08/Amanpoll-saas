<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Pelaporan;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Aset\Domain\Enums\KondisiAset;
use App\Domain\Aset\Domain\Enums\StatusAset;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Aset\Infrastructure\Persistence\Models\KategoriAset;
use App\Domain\Kalibrasi\Infrastructure\Persistence\Models\RencanaKalibrasi;
use App\Domain\Kepatuhan\Infrastructure\Persistence\Models\KepatuhanAset;
use App\Domain\Kepatuhan\Infrastructure\Persistence\Models\PersyaratanKepatuhan;
use App\Domain\Kepatuhan\Infrastructure\Persistence\Models\StandarKepatuhan;
use App\Domain\Kontrak\Infrastructure\Persistence\Models\Kontrak;
use App\Domain\Pelaporan\Application\Services\RegistriKpi;
use App\Domain\Pelaporan\Domain\KatalogKpi;
use App\Domain\Pelaporan\Domain\ValueObjects\FilterMetrik;
use App\Domain\Pelaporan\Domain\ValueObjects\HasilKpi;
use App\Domain\Pemeliharaan\Domain\Enums\StatusPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\BiayaPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerjaAset;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\WaktuHentiAset;
use App\Domain\Penyedia\Infrastructure\Persistence\Models\Penyedia;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\Anggaran;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PesananPembelian;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PosAnggaran;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\Gudang;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\StokSukuCadang;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\SukuCadang;
use App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\UnitOrganisasi;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/** Gate 21, bagian kedua: setiap KPI punya query test. */
final class QueryMetrikTest extends TestCase
{
    use DatabaseTransactions;

    private Organisasi $organisasi;

    private UnitOrganisasi $unit;

    private Lokasi $lokasi;

    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow('2026-06-15 09:00:00');

        $this->organisasi = Organisasi::create(['Kode' => 'ORG-KPI-'.uniqid(), 'Nama' => 'Organisasi KPI']);
        app(KonteksOrganisasi::class)->tetapkan($this->organisasi->Id);

        $this->unit = UnitOrganisasi::create([
            'OrganisasiId' => $this->organisasi->Id,
            'Kode' => 'UNIT-'.uniqid(),
            'Nama' => 'Unit Produksi',
        ]);
        $this->lokasi = Lokasi::create([
            'OrganisasiId' => $this->organisasi->Id,
            'Kode' => 'LOK-'.uniqid(),
            'Nama' => 'Gedung A',
            'Status' => 'Aktif',
        ]);
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_setiap_kpi_dapat_dihitung_tanpa_data_dan_menghasilkan_angka(): void
    {
        $registri = app(RegistriKpi::class);
        $filter = FilterMetrik::bawaan();

        foreach (KatalogKpi::kunci() as $kunci) {
            $hasil = $registri->untuk($kunci)->hitung($kunci, $filter);

            $this->assertInstanceOf(HasilKpi::class, $hasil, "KPI {$kunci} tidak mengembalikan HasilKpi.");
            $this->assertIsFloat($hasil->nilai, "KPI {$kunci} tidak menghasilkan angka.");
            $this->assertSame(0.0, $hasil->nilai, "KPI {$kunci} harus nol saat organisasi belum punya data.");
        }
    }

    public function test_kpi_aset_menghitung_jumlah_nilai_dan_kondisi(): void
    {
        $this->buatAset('AST-1', KondisiAset::Baik->value, 10_000_000);
        $this->buatAset('AST-2', KondisiAset::Baik->value, 5_000_000);
        $this->buatAset('AST-3', KondisiAset::Rusak->value, 1_000_000);

        $this->assertSame(3.0, $this->hitung('aset.jumlah')->nilai);
        $this->assertSame(16_000_000.0, $this->hitung('aset.nilai_perolehan')->nilai);

        // Dua dari tiga aset berkondisi Baik.
        $this->assertSame(66.7, $this->hitung('aset.kondisi')->nilai);
    }

    public function test_kpi_perintah_kerja_memisahkan_aktif_selesai_dan_terlambat(): void
    {
        $this->buatPerintahKerja(['Status' => StatusPerintahKerja::Dikerjakan->value]);
        $this->buatPerintahKerja(['Status' => StatusPerintahKerja::Ditugaskan->value]);
        $this->buatPerintahKerja([
            'Status' => StatusPerintahKerja::Selesai->value,
            'DiselesaikanPada' => CarbonImmutable::now()->subDays(2),
        ]);
        $this->buatPerintahKerja([
            'Status' => StatusPerintahKerja::Dikerjakan->value,
            'BatasPenyelesaianPada' => CarbonImmutable::now()->subDay(),
        ]);

        // Tiga pekerjaan belum tuntas; satu di antaranya lewat batas.
        $this->assertSame(3.0, $this->hitung('perintah_kerja.aktif')->nilai);
        $this->assertSame(1.0, $this->hitung('perintah_kerja.selesai')->nilai);
        $this->assertSame(1.0, $this->hitung('perintah_kerja.terlambat')->nilai);
    }

    public function test_kpi_porsi_pekerjaan_terencana(): void
    {
        $this->buatPerintahKerja(['Jenis' => 'Preventif']);
        $this->buatPerintahKerja(['Jenis' => 'Kalibrasi']);
        $this->buatPerintahKerja(['Jenis' => 'Korektif']);
        $this->buatPerintahKerja(['Jenis' => 'Korektif']);

        // Dua dari empat pekerjaan bersifat terencana.
        $this->assertSame(50.0, $this->hitung('perintah_kerja.terencana')->nilai);
    }

    public function test_kpi_sla_hanya_menghitung_pekerjaan_yang_punya_batas_waktu(): void
    {
        // Tepat waktu.
        $this->buatPerintahKerja([
            'Status' => StatusPerintahKerja::Selesai->value,
            'DiselesaikanPada' => CarbonImmutable::now()->subDays(2),
            'BatasPenyelesaianPada' => CarbonImmutable::now()->subDay(),
        ]);
        // Terlambat.
        $this->buatPerintahKerja([
            'Status' => StatusPerintahKerja::Selesai->value,
            'DiselesaikanPada' => CarbonImmutable::now()->subDay(),
            'BatasPenyelesaianPada' => CarbonImmutable::now()->subDays(3),
        ]);
        // Selesai tanpa SLA: tidak boleh masuk penyebut.
        $this->buatPerintahKerja([
            'Status' => StatusPerintahKerja::Selesai->value,
            'DiselesaikanPada' => CarbonImmutable::now()->subDay(),
        ]);

        $hasil = $this->hitung('sla.kepatuhan_penyelesaian');
        $this->assertSame(50.0, $hasil->nilai);
        $this->assertSame(2.0, $hasil->konteks['Penyebut']);
    }

    public function test_kpi_sla_berisiko_menghitung_batas_dalam_24_jam_ke_depan(): void
    {
        $this->buatPerintahKerja([
            'Status' => StatusPerintahKerja::Dikerjakan->value,
            'BatasPenyelesaianPada' => CarbonImmutable::now()->addHours(5),
        ]);
        $this->buatPerintahKerja([
            'Status' => StatusPerintahKerja::Dikerjakan->value,
            'BatasPenyelesaianPada' => CarbonImmutable::now()->addDays(3),
        ]);

        $this->assertSame(1.0, $this->hitung('sla.berisiko')->nilai);
    }

    public function test_kpi_downtime_mttr_dan_mtbf_dihitung_dari_sesi_waktu_henti(): void
    {
        $aset = $this->buatAset('AST-DT', KondisiAset::Baik->value, 1_000_000);

        // Dua kegagalan tak terencana: 120 menit dan 60 menit.
        $this->buatDowntime($aset, CarbonImmutable::now()->subDays(3), 120, 'TidakTerencana');
        $this->buatDowntime($aset, CarbonImmutable::now()->subDays(2), 60, 'TidakTerencana');

        $filter = new FilterMetrik(
            CarbonImmutable::now()->subDays(4)->startOfDay(),
            CarbonImmutable::now()->endOfDay(),
        );

        // Total 180 menit = 3 jam.
        $this->assertSame(3.0, $this->hitung('downtime.total_jam', $filter)->nilai);

        // MTTR = 180 menit / 2 kegagalan / 60 = 1,5 jam.
        $this->assertSame(1.5, $this->hitung('keandalan.mttr', $filter)->nilai);

        // MTBF = (menit operasional - 180) / 2 kegagalan / 60.
        $menitOperasional = (float) $filter->dari->diffInMinutes($filter->sampai);
        $this->assertSame(
            round(($menitOperasional - 180) / 2 / 60, 1),
            $this->hitung('keandalan.mtbf', $filter)->nilai,
        );

        // Ketersediaan = (operasional - 180) / operasional x 100.
        $this->assertSame(
            round(($menitOperasional - 180) / $menitOperasional * 100, 1),
            $this->hitung('downtime.ketersediaan', $filter)->nilai,
        );
    }

    public function test_downtime_yang_belum_berakhir_dipotong_pada_batas_rentang(): void
    {
        $aset = $this->buatAset('AST-DT2', KondisiAset::Rusak->value, 1_000_000);

        // Sesi masih berjalan sejak 2 jam lalu.
        WaktuHentiAset::create([
            'OrganisasiId' => $this->organisasi->Id,
            'AsetId' => $aset->Id,
            'MulaiPada' => CarbonImmutable::now()->subHours(2),
            'Jenis' => 'TidakTerencana',
            'Alasan' => 'Masih diperbaiki',
        ]);

        $filter = new FilterMetrik(CarbonImmutable::now()->subDay(), CarbonImmutable::now());

        // Dua jam terhitung meski sesi belum ditutup.
        $this->assertSame(2.0, $this->hitung('downtime.total_jam', $filter)->nilai);
    }

    public function test_kpi_biaya_pemeliharaan_dan_biaya_per_aset(): void
    {
        $aset = $this->buatAset('AST-BY', KondisiAset::Baik->value, 1_000_000);
        $perintahKerja = $this->buatPerintahKerja([]);
        PerintahKerjaAset::create([
            'OrganisasiId' => $this->organisasi->Id,
            'PerintahKerjaId' => $perintahKerja->Id,
            'AsetId' => $aset->Id,
            'Utama' => true,
        ]);

        foreach ([['TenagaKerja', 300_000], ['Sparepart', 700_000]] as [$jenis, $jumlah]) {
            BiayaPerintahKerja::create([
                'OrganisasiId' => $this->organisasi->Id,
                'PerintahKerjaId' => $perintahKerja->Id,
                'JenisBiaya' => $jenis,
                'Jumlah' => $jumlah,
                'MataUang' => 'IDR',
                'TanggalBiaya' => CarbonImmutable::now()->subDay()->toDateString(),
            ]);
        }

        $this->assertSame(1_000_000.0, $this->hitung('biaya.pemeliharaan')->nilai);

        // Satu aset dikerjakan, jadi biaya per aset sama dengan totalnya.
        $this->assertSame(1_000_000.0, $this->hitung('biaya.per_aset')->nilai);
    }

    public function test_kpi_stok_menghitung_nilai_dan_yang_di_bawah_minimum(): void
    {
        $gudang = Gudang::create([
            'OrganisasiId' => $this->organisasi->Id,
            'LokasiId' => $this->lokasi->Id,
            'Kode' => 'GD-'.uniqid(),
            'Nama' => 'Gudang Utama',
            'Status' => 'Aktif',
        ]);

        $aman = $this->buatSukuCadang('SC-AMAN', stokMinimum: 5, harga: 100_000);
        $menipis = $this->buatSukuCadang('SC-TIPIS', stokMinimum: 10, harga: 50_000);

        $this->buatStok($gudang, $aman, 20);
        $this->buatStok($gudang, $menipis, 3);

        // 20 x 100.000 + 3 x 50.000 = 2.150.000
        $this->assertSame(2_150_000.0, $this->hitung('stok.nilai')->nilai);
        $this->assertSame(1.0, $this->hitung('stok.di_bawah_minimum')->nilai);
    }

    public function test_kpi_kalibrasi_memisahkan_terlambat_dan_segera_jatuh_tempo(): void
    {
        $aset = $this->buatAset('AST-KAL', KondisiAset::Baik->value, 1_000_000);

        $this->buatRencanaKalibrasi($aset, CarbonImmutable::now()->subDays(5));
        $this->buatRencanaKalibrasi($this->buatAset('AST-KAL2', KondisiAset::Baik->value, 1), CarbonImmutable::now()->addDays(10));
        $this->buatRencanaKalibrasi($this->buatAset('AST-KAL3', KondisiAset::Baik->value, 1), CarbonImmutable::now()->addDays(200));

        // Satu terlambat + satu dalam jendela peringatan 30 hari.
        $this->assertSame(2.0, $this->hitung('kalibrasi.jatuh_tempo')->nilai);

        // Dua dari tiga rencana masih berlaku.
        $this->assertSame(66.7, $this->hitung('kalibrasi.kepatuhan')->nilai);
        $this->assertNotNull($aset->Id);
    }

    public function test_kpi_anggaran_menghitung_serapan_dan_sisa(): void
    {
        $anggaran = Anggaran::create([
            'OrganisasiId' => $this->organisasi->Id,
            'UnitOrganisasiId' => $this->unit->Id,
            'Kode' => 'ANG-'.uniqid(),
            'Nama' => 'Anggaran Pemeliharaan',
            'Tahun' => (int) CarbonImmutable::now()->format('Y'),
            'Jumlah' => 100_000_000,
            'Status' => 'Aktif',
        ]);

        PosAnggaran::create([
            'OrganisasiId' => $this->organisasi->Id,
            'AnggaranId' => $anggaran->Id,
            'Kode' => 'POS-'.uniqid(),
            'Nama' => 'Suku Cadang',
            'Jumlah' => 100_000_000,
            'Terpakai' => 25_000_000,
            'Ditahan' => 5_000_000,
        ]);

        $this->assertSame(25.0, $this->hitung('anggaran.serapan')->nilai);
        $this->assertSame(70_000_000.0, $this->hitung('anggaran.sisa')->nilai);
    }

    public function test_kpi_pengadaan_mengecualikan_draft_dan_dibatalkan_dari_nilai(): void
    {
        $penyedia = Penyedia::create([
            'OrganisasiId' => $this->organisasi->Id,
            'Kode' => 'PNY-'.uniqid(),
            'Nama' => 'Penyedia Uji',
            'Status' => 'Aktif',
        ]);

        foreach ([['Disetujui', 10_000_000], ['Draft', 3_000_000], ['Dibatalkan', 4_000_000]] as [$status, $total]) {
            PesananPembelian::create([
                'OrganisasiId' => $this->organisasi->Id,
                'Nomor' => 'PO-'.uniqid(),
                'PenyediaId' => $penyedia->Id,
                'TanggalPesanan' => CarbonImmutable::now()->subDay()->toDateString(),
                'MataUang' => 'IDR',
                'Total' => $total,
                'Status' => $status,
            ]);
        }

        // Hanya pesanan Disetujui yang dihitung sebagai komitmen belanja.
        $this->assertSame(10_000_000.0, $this->hitung('pengadaan.nilai_pesanan')->nilai);

        // Jumlah pesanan menghitung seluruh status.
        $this->assertSame(3.0, $this->hitung('pengadaan.jumlah_pesanan')->nilai);
    }

    public function test_kpi_kontrak_menghitung_yang_akan_berakhir_dan_nilai_aktif(): void
    {
        $this->buatKontrak(CarbonImmutable::now()->addDays(10), 50_000_000);
        $this->buatKontrak(CarbonImmutable::now()->addDays(300), 20_000_000);

        // Satu kontrak masuk jendela peringatan 30 hari.
        $this->assertSame(1.0, $this->hitung('kontrak.akan_berakhir')->nilai);
        $this->assertSame(70_000_000.0, $this->hitung('kontrak.nilai_aktif')->nilai);
    }

    public function test_kpi_kepatuhan_mengeluarkan_belum_diperiksa_dari_penyebut(): void
    {
        $standar = StandarKepatuhan::create([
            'OrganisasiId' => $this->organisasi->Id,
            'Kode' => 'STD-'.uniqid(),
            'Nama' => 'Standar K3',
            'Status' => 'Aktif',
        ]);
        $persyaratan = PersyaratanKepatuhan::create([
            'OrganisasiId' => $this->organisasi->Id,
            'StandarKepatuhanId' => $standar->Id,
            'Kode' => 'SYA-'.uniqid(),
            'Nama' => 'Pemeriksaan tahunan',
        ]);

        foreach (['Patuh', 'TidakPatuh', 'BelumDiperiksa'] as $indeks => $status) {
            KepatuhanAset::create([
                'OrganisasiId' => $this->organisasi->Id,
                'AsetId' => $this->buatAset('AST-KP'.$indeks, KondisiAset::Baik->value, 1)->Id,
                'PersyaratanKepatuhanId' => $persyaratan->Id,
                'Status' => $status,
            ]);
        }

        // Satu Patuh dari dua yang sudah diperiksa.
        $this->assertSame(50.0, $this->hitung('kepatuhan.tingkat')->nilai);
    }

    public function test_filter_unit_organisasi_menyaring_aset_dan_pekerjaan(): void
    {
        $unitLain = UnitOrganisasi::create([
            'OrganisasiId' => $this->organisasi->Id,
            'Kode' => 'UNIT-LAIN-'.uniqid(),
            'Nama' => 'Unit Lain',
        ]);

        $this->buatAset('AST-U1', KondisiAset::Baik->value, 1_000_000);
        $asetLain = $this->buatAset('AST-U2', KondisiAset::Baik->value, 9_000_000);
        $asetLain->UnitOrganisasiId = $unitLain->Id;
        $asetLain->save();

        $filterUnitIni = new FilterMetrik(
            CarbonImmutable::now()->subDays(29)->startOfDay(),
            CarbonImmutable::now()->endOfDay(),
            [$this->unit->Id],
        );

        $this->assertSame(1.0, $this->hitung('aset.jumlah', $filterUnitIni)->nilai);
        $this->assertSame(1_000_000.0, $this->hitung('aset.nilai_perolehan', $filterUnitIni)->nilai);
        $this->assertSame(2.0, $this->hitung('aset.jumlah')->nilai, 'Tanpa filter, seluruh aset ikut terhitung.');
    }

    public function test_rentang_tanggal_terbalik_dinormalkan(): void
    {
        $filter = FilterMetrik::dariArray(['Dari' => '2026-06-30', 'Sampai' => '2026-06-01']);

        $this->assertSame('2026-06-01', $filter->dari->toDateString());
        $this->assertSame('2026-06-30', $filter->sampai->toDateString());
    }

    private function hitung(string $kunci, ?FilterMetrik $filter = null): HasilKpi
    {
        return app(RegistriKpi::class)
            ->untuk($kunci)
            ->hitung($kunci, $filter ?? FilterMetrik::bawaan());
    }

    private function buatAset(string $kode, string $kondisi, float $harga): Aset
    {
        $kategori = KategoriAset::firstOrCreate(
            ['OrganisasiId' => $this->organisasi->Id, 'Kode' => 'KAT-KPI'],
            ['Nama' => 'Kategori KPI'],
        );

        return Aset::create([
            'OrganisasiId' => $this->organisasi->Id,
            'KategoriAsetId' => $kategori->Id,
            'UnitOrganisasiId' => $this->unit->Id,
            'LokasiId' => $this->lokasi->Id,
            'KodeAset' => $kode.'-'.uniqid(),
            'Nama' => 'Aset '.$kode,
            'Status' => StatusAset::Aktif->value,
            'Kondisi' => $kondisi,
            'HargaPerolehan' => $harga,
        ]);
    }

    /** @param array<string, mixed> $atribut */
    private function buatPerintahKerja(array $atribut): PerintahKerja
    {
        return PerintahKerja::create([
            'OrganisasiId' => $this->organisasi->Id,
            'Nomor' => 'WO-'.uniqid(),
            'Judul' => 'Pekerjaan uji',
            'Jenis' => 'Korektif',
            'Status' => StatusPerintahKerja::Dikerjakan->value,
            'Prioritas' => 'Normal',
            'UnitOrganisasiId' => $this->unit->Id,
            'LokasiId' => $this->lokasi->Id,
            ...$atribut,
        ]);
    }

    private function buatDowntime(Aset $aset, CarbonImmutable $mulai, int $menit, string $jenis): WaktuHentiAset
    {
        return WaktuHentiAset::create([
            'OrganisasiId' => $this->organisasi->Id,
            'AsetId' => $aset->Id,
            'MulaiPada' => $mulai,
            'SelesaiPada' => $mulai->addMinutes($menit),
            'DurasiMenit' => $menit,
            'Jenis' => $jenis,
            'Alasan' => 'Uji',
        ]);
    }

    private function buatSukuCadang(string $kode, float $stokMinimum, float $harga): SukuCadang
    {
        return SukuCadang::create([
            'OrganisasiId' => $this->organisasi->Id,
            'Kode' => $kode.'-'.uniqid(),
            'Nama' => 'Suku cadang '.$kode,
            'SatuanDasar' => 'PCS',
            'StokMinimum' => $stokMinimum,
            'HargaRataRata' => $harga,
            'Status' => 'Aktif',
        ]);
    }

    private function buatStok(Gudang $gudang, SukuCadang $sukuCadang, float $jumlah): StokSukuCadang
    {
        return StokSukuCadang::create([
            'OrganisasiId' => $this->organisasi->Id,
            'GudangId' => $gudang->Id,
            'SukuCadangId' => $sukuCadang->Id,
            'JumlahTersedia' => $jumlah,
        ]);
    }

    private function buatRencanaKalibrasi(Aset $aset, CarbonImmutable $berikutnya): RencanaKalibrasi
    {
        return RencanaKalibrasi::create([
            'OrganisasiId' => $this->organisasi->Id,
            'AsetId' => $aset->Id,
            'IntervalHari' => 365,
            'TanggalMulai' => CarbonImmutable::now()->subYear()->toDateString(),
            'TanggalBerikutnya' => $berikutnya->toDateString(),
            'PeringatanHariSebelum' => 30,
            'Aktif' => true,
        ]);
    }

    private function buatKontrak(CarbonImmutable $berakhir, float $nilai): Kontrak
    {
        return Kontrak::create([
            'OrganisasiId' => $this->organisasi->Id,
            'Nomor' => 'KTR-'.uniqid(),
            'Nama' => 'Kontrak uji',
            'Jenis' => 'Layanan',
            'MulaiPada' => CarbonImmutable::now()->subMonths(6)->toDateString(),
            'BerakhirPada' => $berakhir->toDateString(),
            'Nilai' => $nilai,
            'MataUang' => 'IDR',
            'PeringatanHariSebelum' => 30,
            'Status' => 'Aktif',
        ]);
    }
}
