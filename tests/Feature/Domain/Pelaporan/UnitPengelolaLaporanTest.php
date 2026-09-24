<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Pelaporan;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Aset\Infrastructure\Persistence\Models\KategoriAset;
use App\Domain\Kalibrasi\Infrastructure\Persistence\Models\RencanaKalibrasi;
use App\Domain\Kolaborasi\Application\Services\PenyimpanBerkas;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\Berkas;
use App\Domain\Pelaporan\Application\Services\RegistriKpi;
use App\Domain\Pelaporan\Domain\ValueObjects\FilterMetrik;
use App\Domain\Pelaporan\Domain\ValueObjects\HasilKpi;
use App\Domain\Pelaporan\Infrastructure\Persistence\Models\LaporanTersimpan;
use App\Domain\Pelaporan\Jobs\BuatEksporLaporan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\BiayaPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\Keluhan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\WaktuHentiAset;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\Anggaran;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PosAnggaran;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\Gudang;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\StokSukuCadang;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\SukuCadang;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenggunaPeran;
use App\Domain\Platform\Infrastructure\Persistence\Models\UnitOrganisasi;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\JadwalPemeliharaan;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\RencanaPemeliharaan;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\RencanaPemeliharaanAset;
use App\Shared\Infrastructure\Ekspor\FormatEkspor;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Dimensi Unit Pengelola pada filter metrik, laporan tersimpan, ekspor, dan dasbor (PRD 8.21, TASK 40.05).
 *
 * Dua bagian pemeliharaan dalam satu organisasi: IT dan IPSRS. ICU adalah unit
 * organisasi biasa (pemilik aset), bukan unit pengelola.
 */
final class UnitPengelolaLaporanTest extends KasusPelaporan
{
    private UnitOrganisasi $it;

    private UnitOrganisasi $ipsrs;

    private UnitOrganisasi $icu;

    protected function setUp(): void
    {
        parent::setUp();

        $this->it = $this->buatUnit('IT', 'Unit IT', mengelolaAset: true);
        $this->ipsrs = $this->buatUnit('IPSRS', 'Unit IPSRS', mengelolaAset: true);
        $this->icu = $this->buatUnit('ICU', 'Ruang ICU', mengelolaAset: false);
    }

    public function test_keluhan_difilter_unit_pengelola_memakai_kolom_keluhan_sendiri(): void
    {
        $this->buatKeluhan($this->it);
        $this->buatKeluhan($this->it);
        $this->buatKeluhan($this->ipsrs);
        $this->buatKeluhan(null);
        // Asetnya dikelola IT, tetapi keluhannya diterima antrian IPSRS: yang dihitung kolom keluhan.
        $this->buatKeluhan($this->ipsrs, $this->buatAset($this->it));

        $this->assertSame(2.0, $this->hitung('keluhan.terbuka', $this->filterUnitPengelola($this->it))->nilai);
        $this->assertSame(2.0, $this->hitung('keluhan.terbuka', $this->filterUnitPengelola($this->ipsrs))->nilai);
        $this->assertSame(5.0, $this->hitung('keluhan.terbuka')->nilai);
    }

    /** Filter unit organisasi keluhan tetap membaca unit aset, dan tidak ikut menyaring unit pengelola aset. */
    public function test_filter_unit_organisasi_keluhan_tetap_membaca_unit_aset(): void
    {
        $asetIcuMilikIt = $this->buatAset($this->it, unitOrganisasi: $this->icu);
        $this->buatKeluhan($this->ipsrs, $asetIcuMilikIt);
        $this->buatKeluhan($this->ipsrs);

        $unitIcu = FilterMetrik::dariArray(['UnitOrganisasiId' => [$this->icu->Id]], 'Asia/Jakarta');
        $this->assertSame(1.0, $this->hitung('keluhan.terbuka', $unitIcu)->nilai);

        $icuDanIpsrs = FilterMetrik::dariArray([
            'UnitOrganisasiId' => [$this->icu->Id],
            'UnitPengelolaId' => [$this->ipsrs->Id],
        ], 'Asia/Jakarta');
        $this->assertSame(1.0, $this->hitung('keluhan.terbuka', $icuDanIpsrs)->nilai);

        $icuDanIt = FilterMetrik::dariArray([
            'UnitOrganisasiId' => [$this->icu->Id],
            'UnitPengelolaId' => [$this->it->Id],
        ], 'Asia/Jakarta');
        $this->assertSame(0.0, $this->hitung('keluhan.terbuka', $icuDanIt)->nilai);
    }

    public function test_perintah_kerja_aset_dan_downtime_difilter_unit_pengelola(): void
    {
        $this->buatPerintahKerja($this->it);
        $this->buatPerintahKerja($this->it);
        $this->buatPerintahKerja($this->ipsrs);
        $this->buatPerintahKerja(null);

        $asetIt = $this->buatAset($this->it);
        $asetIpsrs = $this->buatAset($this->ipsrs);
        $this->buatAset(null);
        $this->buatDowntime($asetIt, 60);
        $this->buatDowntime($asetIpsrs, 120);

        $it = $this->filterUnitPengelola($this->it);

        $this->assertSame(2.0, $this->hitung('perintah_kerja.aktif', $it)->nilai);
        $this->assertSame(4.0, $this->hitung('perintah_kerja.aktif')->nilai);

        $this->assertSame(1.0, $this->hitung('aset.jumlah', $it)->nilai);
        $this->assertSame(3.0, $this->hitung('aset.jumlah')->nilai);

        $this->assertSame(1.0, $this->hitung('downtime.total_jam', $it)->nilai);
        $this->assertSame(3.0, $this->hitung('downtime.total_jam')->nilai);
    }

    public function test_stok_mengikuti_unit_pengelola_gudangnya(): void
    {
        $sukuCadang = SukuCadang::create([
            'Kode' => 'SC-'.uniqid(),
            'Nama' => 'Toner',
            'SatuanDasar' => 'PCS',
            'StokMinimum' => 0,
            'HargaRataRata' => 10_000,
            'Status' => 'Aktif',
        ]);
        $this->buatStok($this->buatGudang($this->it), $sukuCadang, 3);
        $this->buatStok($this->buatGudang($this->ipsrs), $sukuCadang, 5);
        $this->buatStok($this->buatGudang(null), $sukuCadang, 7);

        $this->assertSame(30_000.0, $this->hitung('stok.nilai', $this->filterUnitPengelola($this->it))->nilai);
        $this->assertSame(50_000.0, $this->hitung('stok.nilai', $this->filterUnitPengelola($this->ipsrs))->nilai);
        $this->assertSame(150_000.0, $this->hitung('stok.nilai')->nilai);
    }

    public function test_stok_tanpa_filter_hanya_menghitung_gudang_dalam_lingkup_pengguna(): void
    {
        $sukuCadang = SukuCadang::create([
            'Kode' => 'SC-'.uniqid(),
            'Nama' => 'Toner',
            'SatuanDasar' => 'PCS',
            'StokMinimum' => 0,
            'HargaRataRata' => 10_000,
            'Status' => 'Aktif',
        ]);
        $this->buatStok($this->buatGudang($this->it), $sukuCadang, 3);
        $this->buatStok($this->buatGudang($this->ipsrs), $sukuCadang, 5);

        $stafIt = $this->buatPengguna(['Laporan.Lihat']);
        PenggunaPeran::query()->where('PenggunaId', $stafIt->Id)->update(['UnitOrganisasiId' => $this->it->Id]);

        $this->actingAs($stafIt, 'web');

        $this->assertSame(30_000.0, $this->hitung('stok.nilai')->nilai);
    }

    public function test_biaya_mengikuti_unit_pengelola_perintah_kerjanya(): void
    {
        $this->buatBiaya($this->buatPerintahKerja($this->it), 100_000);
        $this->buatBiaya($this->buatPerintahKerja($this->ipsrs), 40_000);

        $this->assertSame(100_000.0, $this->hitung('biaya.pemeliharaan', $this->filterUnitPengelola($this->it))->nilai);
        $this->assertSame(140_000.0, $this->hitung('biaya.pemeliharaan')->nilai);
    }

    /** Rencana yang terisi menentukan; rencana kosong mengikuti unit pengelola asetnya. */
    public function test_kalibrasi_memakai_unit_rencana_lalu_unit_aset(): void
    {
        $asetIpsrs = $this->buatAset($this->ipsrs);

        $this->buatRencanaKalibrasi($asetIpsrs, $this->it);
        $this->buatRencanaKalibrasi($asetIpsrs, null);
        $this->buatRencanaKalibrasi($this->buatAset(null), null);

        $this->assertSame(1.0, $this->hitung('kalibrasi.jatuh_tempo', $this->filterUnitPengelola($this->it))->nilai);
        $this->assertSame(1.0, $this->hitung('kalibrasi.jatuh_tempo', $this->filterUnitPengelola($this->ipsrs))->nilai);
        $this->assertSame(3.0, $this->hitung('kalibrasi.jatuh_tempo')->nilai);
    }

    public function test_preventif_memakai_unit_rencana_lalu_unit_aset(): void
    {
        $asetIpsrs = $this->buatAset($this->ipsrs);

        $this->buatJadwalPreventif($this->buatRencanaPreventif($this->it), $asetIpsrs);
        $this->buatJadwalPreventif($this->buatRencanaPreventif(null), $asetIpsrs);
        $this->buatJadwalPreventif($this->buatRencanaPreventif(null), $this->buatAset(null));

        $this->assertSame(1.0, $this->hitung('preventif.jatuh_tempo', $this->filterUnitPengelola($this->it))->nilai);
        $this->assertSame(1.0, $this->hitung('preventif.jatuh_tempo', $this->filterUnitPengelola($this->ipsrs))->nilai);
        $this->assertSame(3.0, $this->hitung('preventif.jatuh_tempo')->nilai);
    }

    /** Anggaran milik unit organisasi, jadi tidak disaring -- dan kartunya berkata demikian. */
    public function test_anggaran_diberi_tanda_filter_unit_pengelola_tidak_berlaku(): void
    {
        $anggaran = Anggaran::create([
            'Kode' => 'ANG-'.uniqid(),
            'Nama' => 'Anggaran Pemeliharaan',
            'Tahun' => (int) CarbonImmutable::now()->format('Y'),
            'Jumlah' => 1_000,
            'Status' => 'Aktif',
        ]);
        PosAnggaran::create([
            'AnggaranId' => $anggaran->Id,
            'Kode' => 'POS-'.uniqid(),
            'Nama' => 'Suku Cadang',
            'Jumlah' => 1_000,
            'Terpakai' => 250,
            'Ditahan' => 0,
        ]);

        $tersaring = $this->hitung('anggaran.serapan', $this->filterUnitPengelola($this->it));
        $tanpaFilter = $this->hitung('anggaran.serapan');

        $this->assertSame(25.0, $tersaring->nilai);
        $this->assertFalse($tersaring->konteks['FilterUnitPengelolaBerlaku']);
        $this->assertArrayNotHasKey('FilterUnitPengelolaBerlaku', $tanpaFilter->konteks);
    }

    public function test_laporan_tersimpan_menyimpan_dan_memulihkan_filter_unit_pengelola(): void
    {
        $pengguna = $this->buatPengguna(['Laporan.Lihat']);
        $this->buatPerintahKerja($this->it);
        $this->buatPerintahKerja($this->ipsrs);
        $this->buatPerintahKerja($this->ipsrs);

        $this->actingAs($pengguna)
            ->post(route('pelaporan.laporan.store'), [
                'Nama' => 'Pekerjaan IT',
                'Pribadi' => true,
                'Konfigurasi' => [
                    'KunciKpi' => ['perintah_kerja.aktif'],
                    'Filter' => ['Dari' => '2026-01-01', 'Sampai' => '2026-03-31', 'UnitPengelolaId' => [$this->it->Id]],
                ],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $laporan = LaporanTersimpan::query()->where('Nama', 'Pekerjaan IT')->firstOrFail();
        $this->assertSame([$this->it->Id], $laporan->Konfigurasi['Filter']['UnitPengelolaId']);

        // Dibuka tanpa filter di URL: filter tersimpan dipulihkan dan angkanya hanya milik IT.
        $props = $this->actingAs($pengguna)
            ->get(route('pelaporan.laporan.index', ['laporan' => $laporan->Id]))
            ->assertOk()
            ->viewData('page')['props'];

        $this->assertSame([$this->it->Id], $props['filter']['UnitPengelolaId']);
        $this->assertSame('2026-01-01', $props['filter']['Dari']);
        $this->assertSame(1.0, $props['metrik']['perintah_kerja.aktif']['Nilai']);

        // Filter yang dipilih di layar menggantikan filter tersimpan.
        $props = $this->actingAs($pengguna)
            ->get(route('pelaporan.laporan.index', [
                'laporan' => $laporan->Id,
                'Dari' => '2026-01-01',
                'Sampai' => '2026-03-31',
                'UnitPengelolaId' => [$this->ipsrs->Id],
            ]))
            ->assertOk()
            ->viewData('page')['props'];

        $this->assertSame([$this->ipsrs->Id], $props['filter']['UnitPengelolaId']);
        $this->assertSame(2.0, $props['metrik']['perintah_kerja.aktif']['Nilai']);
    }

    /** Unit pengelola yang kini nonaktif tetap boleh menyaring: tiket lamanya masih ada. */
    public function test_unit_pengelola_nonaktif_tetap_dapat_disimpan_sebagai_filter(): void
    {
        $this->ipsrs->update(['Status' => 'Nonaktif']);
        $pengguna = $this->buatPengguna();

        $this->simpanLaporanDenganUnit($pengguna, 'Arsip IPSRS', $this->ipsrs->Id)
            ->assertSessionHasNoErrors();

        $this->assertSame(
            [$this->ipsrs->Id],
            LaporanTersimpan::query()->where('Nama', 'Arsip IPSRS')->firstOrFail()->Konfigurasi['Filter']['UnitPengelolaId'],
        );
    }

    public function test_laporan_tersimpan_menolak_unit_yang_bukan_unit_pengelola_organisasi_ini(): void
    {
        $pengguna = $this->buatPengguna();
        $unitOrganisasiLain = $this->unitPengelolaOrganisasiLain();

        foreach (['bukan pengelola' => $this->icu->Id, 'organisasi lain' => $unitOrganisasiLain->Id, 'karangan' => 'bukan-id'] as $kasus => $unitId) {
            $this->simpanLaporanDenganUnit($pengguna, "Laporan {$kasus}", $unitId)
                ->assertSessionHasErrors('Konfigurasi.Filter.UnitPengelolaId.0');

            $this->assertFalse(LaporanTersimpan::query()->where('Nama', "Laporan {$kasus}")->exists(), $kasus);
        }
    }

    public function test_permintaan_ekspor_menolak_unit_yang_bukan_unit_pengelola(): void
    {
        Queue::fake();
        $pengguna = $this->buatPengguna();

        $this->actingAs($pengguna)
            ->post(route('pelaporan.ekspor.store'), [
                'Judul' => 'Ekspor ICU',
                'Format' => 'Csv',
                'KunciKpi' => ['perintah_kerja.aktif'],
                'Filter' => ['UnitPengelolaId' => [$this->icu->Id]],
            ])
            ->assertSessionHasErrors('Filter.UnitPengelolaId.0');

        Queue::assertNothingPushed();

        $this->actingAs($pengguna)
            ->post(route('pelaporan.ekspor.store'), [
                'Judul' => 'Ekspor IT',
                'Format' => 'Csv',
                'KunciKpi' => ['perintah_kerja.aktif'],
                'Filter' => ['UnitPengelolaId' => [$this->it->Id]],
            ])
            ->assertSessionHasNoErrors();

        Queue::assertPushed(BuatEksporLaporan::class);
    }

    /** Nilai URL yang bukan unit pengelola diabaikan: layar menampilkan filter yang sungguh berlaku. */
    public function test_nilai_unit_pengelola_tidak_sah_di_url_diabaikan(): void
    {
        $pengguna = $this->buatPengguna();
        $laporan = $this->simpanLaporanTanpaFilter($pengguna);
        $this->buatPerintahKerja($this->it);
        $this->buatPerintahKerja(null);
        $unitOrganisasiLain = $this->unitPengelolaOrganisasiLain();

        foreach ([$this->icu->Id, $unitOrganisasiLain->Id, 'bukan-id'] as $unitId) {
            $props = $this->actingAs($pengguna)
                ->get(route('pelaporan.laporan.index', [
                    'laporan' => $laporan->Id,
                    'Dari' => '2026-01-01',
                    'UnitPengelolaId' => [$unitId],
                ]))
                ->assertOk()
                ->viewData('page')['props'];

            $this->assertSame([], $props['filter']['UnitPengelolaId'], $unitId);
            $this->assertSame(2.0, $props['metrik']['perintah_kerja.aktif']['Nilai'], $unitId);
        }

        // Nilai bersarang tidak menjadi galat 500.
        $this->actingAs($pengguna)
            ->get(route('pelaporan.laporan.index', ['laporan' => $laporan->Id, 'UnitPengelolaId' => [[$this->it->Id]]]))
            ->assertOk();
    }

    public function test_dasbor_menawarkan_unit_pengelola_dan_menyaring_widgetnya(): void
    {
        $pengguna = $this->buatPengguna(['Aset.Lihat']);
        $this->buatPerintahKerja($this->it);
        $this->buatPerintahKerja($this->ipsrs);
        $this->buatAset($this->it);
        $this->buatAset($this->ipsrs);
        $this->buatAset($this->ipsrs);

        $this->actingAs($pengguna)
            ->post(route('pelaporan.dasbor.store'), [
                'Nama' => 'Dasbor IT',
                'Bawaan' => true,
                'Komponen' => [
                    ['KunciKpi' => 'perintah_kerja.aktif', 'Bentuk' => 'Angka', 'Lebar' => 1],
                    ['KunciKpi' => 'aset.jumlah', 'Bentuk' => 'Angka', 'Lebar' => 1],
                ],
            ])
            ->assertRedirect();

        $props = $this->actingAs($pengguna)
            ->get(route('dashboard', ['UnitPengelolaId' => [$this->it->Id]]))
            ->assertOk()
            ->viewData('page')['props'];

        $this->assertSame(['Unit IPSRS', 'Unit IT'], array_column($props['pilihanUnitPengelola'], 'Nama'));
        $this->assertSame([$this->it->Id], $props['filter']['UnitPengelolaId']);
        $this->assertSame(1.0, $props['metrik']['perintah_kerja.aktif']['Nilai']);
        $this->assertSame(1.0, $props['metrik']['aset.jumlah']['Nilai']);

        $tanpaFilter = $this->actingAs($pengguna)->get(route('dashboard'))->viewData('page')['props'];
        $this->assertSame(2.0, $tanpaFilter['metrik']['perintah_kerja.aktif']['Nilai']);
        $this->assertSame(3.0, $tanpaFilter['metrik']['aset.jumlah']['Nilai']);
    }

    /** Organisasi tanpa unit bertanda Mengelola Aset tidak melihat pemilihnya, dan filternya tidak berlaku. */
    public function test_organisasi_tanpa_unit_pengelola_tidak_melihat_pemilih(): void
    {
        UnitOrganisasi::query()->whereIn('Id', [$this->it->Id, $this->ipsrs->Id])->update(['MengelolaAset' => false]);
        $pengguna = $this->buatPengguna();
        $this->buatPerintahKerja($this->it);

        $props = $this->actingAs($pengguna)
            ->get(route('dashboard', ['UnitPengelolaId' => [$this->it->Id]]))
            ->assertOk()
            ->viewData('page')['props'];

        $this->assertSame([], $props['pilihanUnitPengelola']);
        $this->assertSame([], $props['filter']['UnitPengelolaId']);
    }

    public function test_ekspor_menyebut_unit_pengelola_di_kop_bila_filternya_dipakai(): void
    {
        Storage::fake('local');
        $pengguna = $this->buatPengguna();
        $this->buatPerintahKerja($this->it);
        $this->buatPerintahKerja($this->ipsrs);

        $isiIt = $this->isiEksporCsv($pengguna, 'Ekspor IT', [$this->it->Id]);
        $this->assertStringContainsString('Unit Pengelola', $isiIt);
        $this->assertStringContainsString('Unit IT', $isiIt);
        $this->assertStringNotContainsString('Unit IPSRS', $isiIt);
        $this->assertMatchesRegularExpression('/Perintah Kerja Aktif[^\n]*Total[^\n]*,1(\.0+)?,/', $isiIt);

        $isiSemua = $this->isiEksporCsv($pengguna, 'Ekspor semua', []);
        $this->assertStringNotContainsString('Unit Pengelola', $isiSemua);
        $this->assertMatchesRegularExpression('/Perintah Kerja Aktif[^\n]*Total[^\n]*,2(\.0+)?,/', $isiSemua);
    }

    private function hitung(string $kunci, ?FilterMetrik $filter = null): HasilKpi
    {
        return app(RegistriKpi::class)->untuk($kunci)->hitung($kunci, $filter ?? FilterMetrik::bawaan('Asia/Jakarta'));
    }

    private function filterUnitPengelola(UnitOrganisasi $unit): FilterMetrik
    {
        return FilterMetrik::dariArray(['UnitPengelolaId' => [$unit->Id]], 'Asia/Jakarta');
    }

    /** @param list<string> $unitPengelolaId */
    private function isiEksporCsv(Pengguna $pengguna, string $judul, array $unitPengelolaId): string
    {
        $job = new BuatEksporLaporan(
            $pengguna->Id,
            ['perintah_kerja.aktif'],
            FilterMetrik::dariArray(['UnitPengelolaId' => $unitPengelolaId], 'Asia/Jakarta')->keArray(),
            FormatEkspor::Csv->value,
            $judul,
        );
        app()->call([$job, 'handle']);

        $berkas = Berkas::query()->where('DiunggahOleh', $pengguna->Id)->where('DataTambahan->Judul', $judul)->firstOrFail();

        // Lewat PenyimpanBerkas: CSV ekspor tersimpan gzip (PRD 11.1).
        $aliran = app(PenyimpanBerkas::class)->bukaAliran($berkas);

        try {
            return (string) stream_get_contents($aliran);
        } finally {
            fclose($aliran);
        }
    }

    /** @return TestResponse<Response> */
    private function simpanLaporanDenganUnit(Pengguna $pengguna, string $nama, string $unitId): TestResponse
    {
        return $this->actingAs($pengguna)->post(route('pelaporan.laporan.store'), [
            'Nama' => $nama,
            'Pribadi' => true,
            'Konfigurasi' => [
                'KunciKpi' => ['perintah_kerja.aktif'],
                'Filter' => ['UnitPengelolaId' => [$unitId]],
            ],
        ]);
    }

    private function simpanLaporanTanpaFilter(Pengguna $pengguna): LaporanTersimpan
    {
        $this->actingAs($pengguna)->post(route('pelaporan.laporan.store'), [
            'Nama' => 'Semua pekerjaan',
            'Pribadi' => true,
            'Konfigurasi' => ['KunciKpi' => ['perintah_kerja.aktif']],
        ])->assertSessionHasNoErrors();

        return LaporanTersimpan::query()->where('Nama', 'Semua pekerjaan')->firstOrFail();
    }

    private function buatUnit(string $kode, string $nama, bool $mengelolaAset): UnitOrganisasi
    {
        return UnitOrganisasi::create([
            'Kode' => $kode.'-'.uniqid(),
            'Nama' => $nama,
            'Status' => 'Aktif',
            'MengelolaAset' => $mengelolaAset,
        ]);
    }

    private function unitPengelolaOrganisasiLain(): UnitOrganisasi
    {
        $konteks = app(KonteksOrganisasi::class);
        $lain = Organisasi::create(['Kode' => 'ORG-LAIN-'.uniqid(), 'Nama' => 'Organisasi Lain']);

        $konteks->tetapkan($lain->Id);
        $unit = $this->buatUnit('IT', 'IT Organisasi Lain', mengelolaAset: true);
        $konteks->tetapkan($this->organisasi->Id);

        return $unit;
    }

    private function buatAset(?UnitOrganisasi $pengelola, ?UnitOrganisasi $unitOrganisasi = null): Aset
    {
        $kategori = KategoriAset::firstOrCreate(['Kode' => 'KAT-UP'], ['Nama' => 'Kategori Unit Pengelola']);

        return Aset::create([
            'KategoriAsetId' => $kategori->Id,
            'UnitOrganisasiId' => $unitOrganisasi?->Id,
            'UnitPengelolaId' => $pengelola?->Id,
            'KodeAset' => 'AST-'.uniqid(),
            'Nama' => 'Aset uji',
            'Status' => 'Aktif',
            'Kondisi' => 'Baik',
            'HargaPerolehan' => 1_000,
        ]);
    }

    private function buatKeluhan(?UnitOrganisasi $pengelola, ?Aset $aset = null): Keluhan
    {
        return Keluhan::create([
            'Nomor' => 'KLH-'.uniqid(),
            'AsetId' => $aset?->Id,
            'UnitPengelolaId' => $pengelola?->Id,
            'Judul' => 'Keluhan uji',
            'Deskripsi' => 'Uji',
            'Prioritas' => 'Normal',
            'Status' => 'Baru',
        ]);
    }

    private function buatPerintahKerja(?UnitOrganisasi $pengelola): PerintahKerja
    {
        return PerintahKerja::create([
            'Nomor' => 'WO-'.uniqid(),
            'Judul' => 'Pekerjaan uji',
            'Jenis' => 'Korektif',
            'Status' => 'Dikerjakan',
            'Prioritas' => 'Normal',
            'UnitPengelolaId' => $pengelola?->Id,
        ]);
    }

    private function buatBiaya(PerintahKerja $perintahKerja, float $jumlah): void
    {
        BiayaPerintahKerja::create([
            'PerintahKerjaId' => $perintahKerja->Id,
            'JenisBiaya' => 'TenagaKerja',
            'Jumlah' => $jumlah,
            'MataUang' => 'IDR',
            'TanggalBiaya' => CarbonImmutable::now()->subDay()->toDateString(),
        ]);
    }

    private function buatDowntime(Aset $aset, int $menit): void
    {
        $mulai = CarbonImmutable::now()->subDays(2);

        WaktuHentiAset::create([
            'AsetId' => $aset->Id,
            'MulaiPada' => $mulai,
            'SelesaiPada' => $mulai->addMinutes($menit),
            'DurasiMenit' => $menit,
            'Jenis' => 'Kegagalan',
            'Alasan' => 'Uji',
        ]);
    }

    private function buatGudang(?UnitOrganisasi $pengelola): Gudang
    {
        return Gudang::create([
            'Kode' => 'GD-'.uniqid(),
            'Nama' => 'Gudang uji',
            'Status' => 'Aktif',
            'UnitPengelolaId' => $pengelola?->Id,
        ]);
    }

    private function buatStok(Gudang $gudang, SukuCadang $sukuCadang, float $jumlah): void
    {
        StokSukuCadang::create([
            'GudangId' => $gudang->Id,
            'SukuCadangId' => $sukuCadang->Id,
            'JumlahTersedia' => $jumlah,
        ]);
    }

    private function buatRencanaKalibrasi(Aset $aset, ?UnitOrganisasi $pengelola): void
    {
        RencanaKalibrasi::create([
            'AsetId' => $aset->Id,
            'UnitPengelolaId' => $pengelola?->Id,
            'IntervalHari' => 365,
            'TanggalMulai' => CarbonImmutable::now()->subYear()->toDateString(),
            'TanggalBerikutnya' => CarbonImmutable::now()->subDays(3)->toDateString(),
            'PeringatanHariSebelum' => 30,
            'Aktif' => true,
        ]);
    }

    private function buatRencanaPreventif(?UnitOrganisasi $pengelola): RencanaPemeliharaan
    {
        return RencanaPemeliharaan::create([
            'Kode' => 'RPM-'.uniqid(),
            'Nama' => 'Rencana uji',
            'UnitPengelolaId' => $pengelola?->Id,
        ]);
    }

    private function buatJadwalPreventif(RencanaPemeliharaan $rencana, Aset $aset): void
    {
        $rencanaAset = RencanaPemeliharaanAset::create([
            'RencanaPemeliharaanId' => $rencana->Id,
            'AsetId' => $aset->Id,
            'TanggalMulai' => CarbonImmutable::now()->subMonth()->toDateString(),
            'TanggalBerikutnya' => CarbonImmutable::now()->addMonth()->toDateString(),
            'Aktif' => true,
        ]);

        JadwalPemeliharaan::create([
            'RencanaPemeliharaanAsetId' => $rencanaAset->Id,
            'TanggalJadwal' => CarbonImmutable::now()->subDays(3)->toDateString(),
            'Status' => 'Terjadwal',
        ]);
    }
}
