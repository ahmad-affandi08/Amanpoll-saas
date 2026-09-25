<?php

declare(strict_types=1);

namespace Tests\Feature\Pemeliharaan;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Aset\Application\Actions\CatatPembacaanMeter;
use App\Domain\Aset\Domain\Enums\StatusAset;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Aset\Infrastructure\Persistence\Models\KategoriAset;
use App\Domain\Aset\Infrastructure\Persistence\Models\MeterAset;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use App\Domain\Platform\Infrastructure\Persistence\Models\Izin;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenggunaPeran;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Domain\Platform\Infrastructure\Persistence\Models\PeranIzin;
use App\Domain\PreventifInspeksi\Application\Actions\JadwalkanPemeliharaanPreventif;
use App\Domain\PreventifInspeksi\Application\Actions\KelolaRencanaPemeliharaan;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\RencanaPemeliharaan;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\RencanaPemeliharaanAset;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Rencana preventif berbasis pemakaian meter: "servis setiap 500 jam" jatuh tempo
 * saat pembacaan meter kumulatif aset melewati pembacaan servis terakhir + ambang.
 * Pada strategi kombinasi, kalender atau meter yang lebih dulu tercapai memicu servis
 * dan keduanya dihitung ulang dari servis itu.
 */
final class PreventifBerbasisMeterTest extends TestCase
{
    use RefreshDatabase;

    private Organisasi $organisasi;

    private Pengguna $manajer;

    private Aset $genset;

    private MeterAset $jamMesin;

    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow('2026-05-04 02:00:00');

        $this->organisasi = Organisasi::create(['Kode' => 'ORG-MTR', 'Nama' => 'Organisasi Meter']);
        $this->manajer = $this->buatPengguna(['Pemeliharaan.Kelola']);
        app(KonteksOrganisasi::class)->tetapkan($this->organisasi->Id);

        $this->genset = $this->buatAset('Genset Utama 500 kVA');
        $this->jamMesin = MeterAset::create(['AsetId' => $this->genset->Id, 'Nama' => 'Jam Mesin', 'Satuan' => 'jam', 'Jenis' => 'Kumulatif', 'NilaiAwal' => 1000]);
        $this->bacaMeter($this->jamMesin, 1200, '2026-05-01 08:00:00');
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_rencana_meter_jatuh_tempo_saat_pemakaian_melewati_ambang(): void
    {
        $rencana = $this->buatRencana(['StrategiJadwal' => 'PenggunaanMeter', 'AmbangMeter' => 500]);
        $asetPlan = $this->kelola()->tetapkanAset($rencana, $this->genset->Id);

        $this->assertSame($this->jamMesin->Id, $asetPlan->MeterAsetId);
        $this->assertEquals(1700, $asetPlan->NilaiMeterBerikutnya, 'Ambang dihitung dari pembacaan terakhir.');
        $this->assertNull($asetPlan->TanggalBerikutnya, 'Rencana meter murni tidak punya jatuh tempo kalender.');

        $this->jadwalkan();
        $this->assertSame(0, $this->jumlahPerintahKerja(), 'Pemakaian 1.200 jam belum mencapai 1.700.');

        $this->bacaMeter($this->jamMesin, 1750, '2026-05-03 08:00:00');
        $this->jadwalkan();
        $this->jadwalkan();

        $perintahKerja = PerintahKerja::query()->sole();
        $this->assertSame('Preventif', $perintahKerja->Jenis);
        $this->assertStringContainsString('Jam Mesin mencapai 1.750 jam', (string) $perintahKerja->Deskripsi);
        $this->assertEquals(2250, $asetPlan->refresh()->NilaiMeterBerikutnya, 'Ambang berikutnya dihitung dari pembacaan saat servis.');
    }

    public function test_pembacaan_bertanggal_depan_tidak_memicu_servis(): void
    {
        $rencana = $this->buatRencana(['StrategiJadwal' => 'PenggunaanMeter', 'AmbangMeter' => 500]);
        $this->kelola()->tetapkanAset($rencana, $this->genset->Id);

        $this->bacaMeter($this->jamMesin, 1900, '2026-05-10 08:00:00');
        $this->jadwalkan();

        $this->assertSame(0, $this->jumlahPerintahKerja(), 'Pembacaan tanggal 10 belum terjadi pada tanggal 4.');
    }

    public function test_kombinasi_meter_lebih_dulu_memicu_servis_dan_mengatur_ulang_kalender(): void
    {
        $rencana = $this->buatRencana(['StrategiJadwal' => 'Kombinasi', 'IntervalNilai' => 6, 'IntervalSatuan' => 'Bulan', 'AmbangMeter' => 500]);
        $asetPlan = $this->kelola()->tetapkanAset($rencana, $this->genset->Id, '2026-05-04');
        $this->assertSame('2026-11-04', $asetPlan->TanggalBerikutnya?->toDateString());

        $this->bacaMeter($this->jamMesin, 1710, '2026-05-04 01:00:00');
        $this->jadwalkan();

        $this->assertSame(1, $this->jumlahPerintahKerja());
        $asetPlan->refresh();
        $this->assertSame('2026-11-04', $asetPlan->TanggalBerikutnya?->toDateString(), 'Servis hari ini: kalender dihitung ulang dari hari ini.');
        $this->assertEquals(2210, $asetPlan->NilaiMeterBerikutnya);
    }

    public function test_kombinasi_kalender_lebih_dulu_memicu_servis_dan_mengatur_ulang_meter(): void
    {
        $rencana = $this->buatRencana(['StrategiJadwal' => 'Kombinasi', 'IntervalNilai' => 1, 'IntervalSatuan' => 'Bulan', 'AmbangMeter' => 500]);
        $asetPlan = $this->kelola()->tetapkanAset($rencana, $this->genset->Id, '2026-04-06', '2026-05-06');

        $this->bacaMeter($this->jamMesin, 1300, '2026-05-04 01:00:00');
        $this->jadwalkan();

        $this->assertSame(1, $this->jumlahPerintahKerja());
        $asetPlan->refresh();
        $this->assertSame('2026-06-06', $asetPlan->TanggalBerikutnya?->toDateString());
        $this->assertEquals(1800, $asetPlan->NilaiMeterBerikutnya, 'Servis karena kalender juga memulai hitungan meter baru.');
    }

    public function test_rencana_interval_tidak_terpengaruh_meter(): void
    {
        $rencana = $this->buatRencana(['StrategiJadwal' => 'Interval', 'IntervalNilai' => 1, 'IntervalSatuan' => 'Bulan', 'AmbangMeter' => 100]);
        $asetPlan = $this->kelola()->tetapkanAset($rencana, $this->genset->Id, '2026-05-04');

        $this->bacaMeter($this->jamMesin, 5000, '2026-05-04 01:00:00');
        $this->jadwalkan();

        $this->assertSame(0, $this->jumlahPerintahKerja());
        $this->assertNull($asetPlan->refresh()->NilaiMeterBerikutnya);
        $this->assertNull($rencana->refresh()->AmbangMeter, 'Ambang tidak disimpan untuk rencana kalender.');
    }

    public function test_penetapan_aset_menolak_aset_tanpa_meter_atau_dengan_meter_ganda_tanpa_pilihan(): void
    {
        $rencana = $this->buatRencana(['StrategiJadwal' => 'PenggunaanMeter', 'AmbangMeter' => 500]);
        $tanpaMeter = $this->buatAset('Pompa Hydrant');
        $meterGanda = $this->buatAset('Forklift Diesel');
        $jam = MeterAset::create(['AsetId' => $meterGanda->Id, 'Nama' => 'Jam Operasi', 'Satuan' => 'jam', 'Jenis' => 'Kumulatif']);
        MeterAset::create(['AsetId' => $meterGanda->Id, 'Nama' => 'Odometer', 'Satuan' => 'km', 'Jenis' => 'Kumulatif']);
        $suhu = MeterAset::create(['AsetId' => $tanpaMeter->Id, 'Nama' => 'Suhu Bantalan', 'Satuan' => 'C', 'Jenis' => 'NonKumulatif']);

        foreach ([[$tanpaMeter, null], [$meterGanda, null], [$tanpaMeter, $suhu->Id], [$tanpaMeter, $jam->Id]] as [$aset, $meterId]) {
            try {
                $this->kelola()->tetapkanAset($rencana, $aset->Id, meterAsetId: $meterId);
                $this->fail("Penetapan {$aset->Nama} dengan meter ".var_export($meterId, true).' seharusnya ditolak.');
            } catch (AturanBisnisDilanggar) {
                // diharapkan
            }
        }

        $this->assertSame(0, RencanaPemeliharaanAset::query()->count());

        $asetPlan = $this->kelola()->tetapkanAset($rencana, $meterGanda->Id, meterAsetId: $jam->Id);
        $this->assertSame($jam->Id, $asetPlan->MeterAsetId);
        $this->assertEquals(500, $asetPlan->NilaiMeterBerikutnya);
    }

    public function test_aset_lama_tanpa_titik_awal_meter_diberi_titik_awal_tanpa_langsung_dipicu(): void
    {
        $rencana = $this->buatRencana(['StrategiJadwal' => 'Kombinasi', 'IntervalNilai' => 6, 'IntervalSatuan' => 'Bulan', 'AmbangMeter' => 500]);
        $asetPlan = $this->kelola()->tetapkanAset($rencana, $this->genset->Id, '2026-05-04');
        $asetPlan->update(['MeterAsetId' => null, 'NilaiMeterBerikutnya' => null]);

        $this->jadwalkan();

        $this->assertSame(0, $this->jumlahPerintahKerja());
        $asetPlan->refresh();
        $this->assertSame($this->jamMesin->Id, $asetPlan->MeterAsetId);
        $this->assertEquals(1700, $asetPlan->NilaiMeterBerikutnya);
    }

    public function test_mengubah_ambang_mempertahankan_titik_servis_terakhir(): void
    {
        $rencana = $this->buatRencana(['StrategiJadwal' => 'PenggunaanMeter', 'AmbangMeter' => 500]);
        $asetPlan = $this->kelola()->tetapkanAset($rencana, $this->genset->Id);

        $this->kelola()->perbarui($rencana, ['AmbangMeter' => 300], $this->manajer->Id);

        $this->assertEquals(1500, $asetPlan->refresh()->NilaiMeterBerikutnya, 'Titik awal 1.200 jam tetap; ambang baru 300.');
    }

    public function test_mengubah_rencana_meter_menjadi_kombinasi_memberi_jatuh_tempo_kalender(): void
    {
        $rencana = $this->buatRencana(['StrategiJadwal' => 'PenggunaanMeter', 'AmbangMeter' => 500]);
        $asetPlan = $this->kelola()->tetapkanAset($rencana, $this->genset->Id);

        $this->kelola()->perbarui($rencana, ['StrategiJadwal' => 'Kombinasi', 'IntervalNilai' => 3, 'IntervalSatuan' => 'Bulan'], $this->manajer->Id);

        $this->assertNotNull($asetPlan->refresh()->TanggalBerikutnya, 'Tanpa tanggal, pemicu kalender tidak akan pernah jatuh tempo.');
        $this->assertEquals(1700, $asetPlan->NilaiMeterBerikutnya);
    }

    public function test_formulir_rencana_meter_mewajibkan_ambang_dan_tidak_mewajibkan_interval(): void
    {
        app(KonteksOrganisasi::class)->bersihkan();
        $dasar = ['Nama' => 'Servis Genset 500 Jam', 'Prioritas' => 'Normal'];

        $this->actingAs($this->manajer)
            ->post(route('preventifInspeksi.rencana-pemeliharaan.store'), [...$dasar, 'StrategiJadwal' => 'PenggunaanMeter'])
            ->assertSessionHasErrors('AmbangMeter');

        $this->actingAs($this->manajer)
            ->post(route('preventifInspeksi.rencana-pemeliharaan.store'), [...$dasar, 'StrategiJadwal' => 'Kombinasi', 'AmbangMeter' => 500])
            ->assertSessionHasErrors('IntervalNilai');

        $this->actingAs($this->manajer)
            ->post(route('preventifInspeksi.rencana-pemeliharaan.store'), [...$dasar, 'StrategiJadwal' => 'PenggunaanMeter', 'AmbangMeter' => 500])
            ->assertSessionHasNoErrors();

        app(KonteksOrganisasi::class)->tetapkan($this->organisasi->Id);
        $rencana = RencanaPemeliharaan::query()->sole();
        $this->assertSame('PenggunaanMeter', $rencana->StrategiJadwal);
        $this->assertTrue($rencana->BerdasarkanMeter);
        $this->assertEquals(500, $rencana->AmbangMeter);
        $this->assertNull($rencana->IntervalNilai);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function buatRencana(array $data): RencanaPemeliharaan
    {
        return $this->kelola()->buat([
            'Nama' => 'Servis Berkala Genset',
            'Prioritas' => 'Normal',
            'BuatPerintahKerjaHariSebelum' => 7,
            ...$data,
        ], $this->manajer->Id);
    }

    private function jadwalkan(): void
    {
        app(JadwalkanPemeliharaanPreventif::class)->jalankan(organisasiId: $this->organisasi->Id, penggunaId: $this->manajer->Id);
    }

    private function jumlahPerintahKerja(): int
    {
        return PerintahKerja::query()->where('Jenis', 'Preventif')->count();
    }

    private function bacaMeter(MeterAset $meter, float $nilai, string $waktu): void
    {
        app(CatatPembacaanMeter::class)->jalankan($meter, ['Nilai' => $nilai, 'DibacaPada' => $waktu], $this->manajer->Id);
    }

    private function kelola(): KelolaRencanaPemeliharaan
    {
        return app(KelolaRencanaPemeliharaan::class);
    }

    private function buatAset(string $nama): Aset
    {
        $kategori = KategoriAset::query()->first() ?? KategoriAset::create(['Kode' => 'KAT-UTL', 'Nama' => 'Utilitas']);

        return Aset::create([
            'KategoriAsetId' => $kategori->Id,
            'Nama' => $nama,
            'Status' => StatusAset::Aktif->value,
        ]);
    }

    /**
     * @param  list<string>  $izin
     */
    private function buatPengguna(array $izin): Pengguna
    {
        $pengguna = Pengguna::create([
            'OrganisasiId' => $this->organisasi->Id,
            'Nama' => 'Manajer Pemeliharaan',
            'Email' => 'manajer.'.uniqid().'@amanpoll.test',
            'KataSandi' => 'rahasia',
            'Status' => 'Aktif',
        ]);

        app(KonteksOrganisasi::class)->tetapkan($this->organisasi->Id);
        $peran = Peran::create(['Kode' => 'PERAN-'.uniqid(), 'Nama' => 'Manajer']);
        foreach ($izin as $kode) {
            $modelIzin = Izin::firstOrCreate(['Kode' => $kode], ['Nama' => $kode, 'Modul' => 'Pemeliharaan']);
            PeranIzin::create(['PeranId' => $peran->Id, 'IzinId' => $modelIzin->Id]);
        }
        PenggunaPeran::create(['PenggunaId' => $pengguna->Id, 'PeranId' => $peran->Id]);

        return $pengguna;
    }
}
