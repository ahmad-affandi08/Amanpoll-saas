<?php

declare(strict_types=1);

namespace Tests\Feature\AlurKritis;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Aset\Domain\Enums\StatusAset;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Aset\Infrastructure\Persistence\Models\KategoriAset;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\RiwayatStatusPerintahKerja;
use App\Domain\Platform\Infrastructure\Persistence\Models\Izin;
use App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\NomorDokumen;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenggunaPeran;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Domain\Platform\Infrastructure\Persistence\Models\PeranIzin;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\ButirTemplatDaftarPeriksa;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\JadwalPemeliharaan;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\JawabanDaftarPeriksa;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\PelaksanaanDaftarPeriksa;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\RencanaPemeliharaan;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\RencanaPemeliharaanAset;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\TemplatDaftarPeriksa;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Alur kritis FASE 26.03: preventif -> jadwal -> perintah kerja -> daftar periksa -> tutup.
 *
 * Seluruh langkah berjalan lewat rute HTTP. Organisasi kedua sengaja
 * memiliki rencana yang juga jatuh tempo, sehingga penjadwal yang lupa
 * membatasi diri pada organisasi pemanggil akan ketahuan.
 */
final class AlurPreventifSampaiTutupTest extends TestCase
{
    use RefreshDatabase;

    /** @var array{organisasi: Organisasi, manajer: Pengguna, teknisi: Pengguna, aset: Aset, asetKedua: Aset} */
    private array $a;

    /** @var array{organisasi: Organisasi, asetPlan: RencanaPemeliharaanAset} */
    private array $b;

    protected function setUp(): void
    {
        parent::setUp();

        // Senin 21 September 2026 pukul 08:00 WIB.
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-09-21 01:00:00', 'UTC'));

        $this->a = $this->semaiOrganisasiA();
        $this->b = $this->semaiOrganisasiBDenganRencanaJatuhTempo();
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_rencana_preventif_dijadwalkan_menjadi_perintah_kerja_dengan_daftar_periksa_lalu_ditutup(): void
    {
        // 1. Templat daftar periksa dan rencana bulanan, lalu dua aset didaftarkan.
        [$templat, $butirOli, $butirTekanan] = $this->buatTemplat();
        $rencana = $this->buatRencana($templat);
        $this->tetapkanKonteks($this->a['organisasi']);
        $this->assertSame($templat->Id, $rencana->TemplatDaftarPeriksaId);

        $asetPlan = $this->daftarkanAset($rencana, $this->a['aset'], '2026-09-25');
        $asetPlanJauh = $this->daftarkanAset($rencana, $this->a['asetKedua'], '2026-10-15');
        $this->assertSame('2026-09-25', $asetPlan->TanggalBerikutnya->toDateString());

        // 2. Penjadwal hanya mengambil aset yang jatuh tempo dalam horizon 7 hari, dan hanya milik organisasi pemanggil.
        $this->jalankanPenjadwal()->assertSessionHas('sukses', 'Penjadwalan selesai. Jadwal dibuat: 1, Perintah Kerja dibuat: 1, Dilewati: 0.');

        $this->tetapkanKonteks($this->a['organisasi']);
        $jadwal = JadwalPemeliharaan::query()->sole();
        $this->assertSame($asetPlan->Id, $jadwal->RencanaPemeliharaanAsetId);
        $this->assertSame('2026-09-25', $jadwal->TanggalJadwal->toDateString());
        $this->assertSame('Terjadwal', $jadwal->Status);
        $this->assertSame('2026-10-25', $asetPlan->fresh()?->TanggalBerikutnya->toDateString());
        $this->assertSame('2026-10-15', $asetPlanJauh->fresh()?->TanggalBerikutnya->toDateString());

        $perintahKerja = PerintahKerja::query()->sole();
        $this->assertSame($jadwal->PerintahKerjaId, $perintahKerja->Id);
        $this->assertSame('WO-2026-0001', $perintahKerja->Nomor);
        $this->assertSame('Preventif', $perintahKerja->Jenis);
        $this->assertSame('Tinggi', $perintahKerja->Prioritas);
        $this->assertSame('Draf', $perintahKerja->Status);
        $this->assertSame($this->a['manajer']->Id, $perintahKerja->DibuatOleh);
        $this->assertSame('2026-09-25 00:00:00', $perintahKerja->DijadwalkanMulaiPada?->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s'));
        $this->assertDatabaseHas('PerintahKerjaAset', ['PerintahKerjaId' => $perintahKerja->Id, 'AsetId' => $this->a['aset']->Id, 'Utama' => true]);

        $daftarPeriksa = PelaksanaanDaftarPeriksa::query()->where('PerintahKerjaId', $perintahKerja->Id)->sole();
        $this->assertSame($templat->Id, $daftarPeriksa->TemplatDaftarPeriksaId);
        $this->assertSame($this->a['aset']->Id, $daftarPeriksa->AsetId);
        $this->assertSame('Draft', $daftarPeriksa->Status);

        $this->tetapkanKonteks($this->b['organisasi']);
        $this->assertSame(0, JadwalPemeliharaan::query()->count());
        $this->assertSame(0, PerintahKerja::query()->count());
        $this->assertSame('2026-09-24', $this->b['asetPlan']->fresh()?->TanggalBerikutnya->toDateString());

        // 3. Penjadwal yang dijalankan ulang tidak menggandakan jadwal maupun perintah kerja.
        $this->jalankanPenjadwal()->assertSessionHas('sukses', 'Penjadwalan selesai. Jadwal dibuat: 0, Perintah Kerja dibuat: 0, Dilewati: 0.');
        $this->tetapkanKonteks($this->a['organisasi']);
        $this->assertSame(1, JadwalPemeliharaan::query()->count());
        $this->assertSame(1, PerintahKerja::query()->count());

        // 4. Teknisi yang belum ditugaskan tidak boleh mengisi daftar periksa.
        $this->actingAs($this->a['teknisi'])->put("/preventif-inspeksi/pelaksanaan-daftar-periksa/{$daftarPeriksa->Id}/jawaban", [
            'jawaban' => [['ButirTemplatDaftarPeriksaId' => $butirOli->Id, 'NilaiBoolean' => true]],
        ])->assertForbidden();

        // 5. Hari pelaksanaan: jadwalkan, tugaskan, terima, kerjakan.
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-09-25 01:00:00', 'UTC'));
        $this->ubahStatus($this->a['manajer'], $perintahKerja, 'Terjadwal');
        $this->tugaskanDanMulai($perintahKerja);

        // 6. Daftar periksa diisi teknisi yang kini ditugaskan; nilai di luar rentang dinilai tidak sesuai.
        $this->isiDaftarPeriksa($daftarPeriksa, $butirOli, $butirTekanan)->assertSessionDoesntHaveErrors();
        $this->tetapkanKonteks($this->a['organisasi']);
        $this->assertSame('SedangDikerjakan', $daftarPeriksa->fresh()?->Status);
        $this->assertFalse(JawabanDaftarPeriksa::query()->where('PelaksanaanDaftarPeriksaId', $daftarPeriksa->Id)->where('ButirTemplatDaftarPeriksaId', $butirTekanan->Id)->sole()->Sesuai);

        $this->finalisasiDaftarPeriksa($daftarPeriksa)->assertSessionHas('sukses', 'Daftar periksa berhasil diselesaikan dengan skor 50.00%.');
        $this->tetapkanKonteks($this->a['organisasi']);
        $daftarPeriksa->refresh();
        $this->assertSame('Selesai', $daftarPeriksa->Status);
        $this->assertSame(50.0, (float) $daftarPeriksa->Skor);
        $this->assertSame('2026-09-25 08:20:00', $daftarPeriksa->SelesaiPada?->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s'));

        // 7. Setelah final, jawaban terkunci: perubahan ditolak dan nilai lama bertahan.
        $this->actingAs($this->a['teknisi'])->put("/preventif-inspeksi/pelaksanaan-daftar-periksa/{$daftarPeriksa->Id}/jawaban", [
            'jawaban' => [['ButirTemplatDaftarPeriksaId' => $butirTekanan->Id, 'NilaiAngka' => 7]],
        ])->assertStatus(422);
        $this->tetapkanKonteks($this->a['organisasi']);
        $this->assertSame(9.5, (float) JawabanDaftarPeriksa::query()->where('PelaksanaanDaftarPeriksaId', $daftarPeriksa->Id)->where('ButirTemplatDaftarPeriksaId', $butirTekanan->Id)->sole()->NilaiAngka);

        // 8. Verifikasi dan tutup perintah kerja.
        $this->tutupPerintahKerja($perintahKerja);
        $perintahKerja->refresh();
        $this->assertSame('Ditutup', $perintahKerja->Status);
        $this->assertSame('2026-09-25 08:35:00', $perintahKerja->DitutupPada?->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s'));

        // 9. Jejak status berurutan dan pembuatan jadwal otomatis teraudit.
        $this->assertSame(
            ['Draf', 'Terjadwal', 'Ditugaskan', 'Diterima', 'Dikerjakan', 'MenungguVerifikasi', 'Selesai', 'Ditutup'],
            RiwayatStatusPerintahKerja::query()->where('PerintahKerjaId', $perintahKerja->Id)->orderBy('DiubahPada')->orderBy('Id')->pluck('StatusSesudah')->all(),
        );
        $this->assertSame(1, DB::table('CatatanAudit')
            ->where('OrganisasiId', $this->a['organisasi']->Id)
            ->where('Aksi', 'JadwalPemeliharaan.DibuatOtomatis')
            ->where('EntitasId', $jadwal->Id)
            ->count());

        // 10. Organisasi kedua tetap utuh sampai akhir.
        $this->tetapkanKonteks($this->b['organisasi']);
        $this->assertSame(0, JadwalPemeliharaan::query()->count());
        $this->assertSame('2026-09-24', $this->b['asetPlan']->fresh()?->TanggalBerikutnya->toDateString());
    }

    /**
     * BUG: tidak ada kode yang memindahkan JadwalPemeliharaan dari 'Terjadwal'.
     *
     * KPI `preventif.jatuh_tempo` menghitung jadwal berstatus Terjadwal yang
     * tanggalnya sudah lewat, dan `preventif.kepatuhan` membagi jadwal
     * berstatus Selesai dengan seluruh jadwal (KatalogKpi, QueryPreventif).
     * Karena status itu tidak pernah berubah, preventif yang sudah dikerjakan
     * dan ditutup tetap terhitung terlambat, dan kepatuhan preventif selalu 0%.
     */
    public function test_menutup_perintah_kerja_preventif_menandai_jadwal_pemeliharaan_selesai(): void
    {
        [$templat, $butirOli, $butirTekanan] = $this->buatTemplat();
        $rencana = $this->buatRencana($templat);
        $this->daftarkanAset($rencana, $this->a['aset'], '2026-09-25');
        $this->jalankanPenjadwal()->assertSessionHas('sukses', 'Penjadwalan selesai. Jadwal dibuat: 1, Perintah Kerja dibuat: 1, Dilewati: 0.');

        $this->tetapkanKonteks($this->a['organisasi']);
        $jadwal = JadwalPemeliharaan::query()->sole();
        $this->assertSame('Terjadwal', $jadwal->Status);
        $perintahKerja = PerintahKerja::query()->findOrFail($jadwal->PerintahKerjaId);
        $daftarPeriksa = PelaksanaanDaftarPeriksa::query()->where('PerintahKerjaId', $perintahKerja->Id)->sole();

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-09-25 01:00:00', 'UTC'));
        $this->ubahStatus($this->a['manajer'], $perintahKerja, 'Terjadwal');
        $this->tugaskanDanMulai($perintahKerja);
        $this->isiDaftarPeriksa($daftarPeriksa, $butirOli, $butirTekanan)->assertSessionDoesntHaveErrors();
        $this->finalisasiDaftarPeriksa($daftarPeriksa)->assertSessionDoesntHaveErrors();
        $this->tutupPerintahKerja($perintahKerja);

        $this->tetapkanKonteks($this->a['organisasi']);
        $this->assertSame('Ditutup', $perintahKerja->fresh()?->Status);
        $this->assertSame(
            'Selesai',
            $jadwal->fresh()?->Status,
            'Perintah kerja preventif sudah Ditutup, tetapi jadwalnya masih Terjadwal sehingga terhitung jatuh tempo dan tidak pernah masuk kepatuhan.',
        );
    }

    /** @return array{TemplatDaftarPeriksa, ButirTemplatDaftarPeriksa, ButirTemplatDaftarPeriksa} */
    private function buatTemplat(): array
    {
        $this->actingAs($this->a['manajer'])->post('/preventif-inspeksi/templat-daftar-periksa', [
            'Kode' => 'CK-PM-KMP',
            'Nama' => 'Preventif Kompresor Medis',
            'Jenis' => 'Pemeliharaan',
        ])->assertSessionDoesntHaveErrors()->assertRedirect();

        $this->tetapkanKonteks($this->a['organisasi']);
        $templat = TemplatDaftarPeriksa::query()->where('Kode', 'CK-PM-KMP')->sole();

        $this->actingAs($this->a['manajer'])->post("/preventif-inspeksi/templat-daftar-periksa/{$templat->Id}/butir", [
            'Kode' => 'OLI',
            'Pertanyaan' => 'Level oli kompresor di garis normal?',
            'TipeJawaban' => 'YaTidak',
            'Wajib' => true,
        ])->assertSessionDoesntHaveErrors();
        $this->actingAs($this->a['manajer'])->post("/preventif-inspeksi/templat-daftar-periksa/{$templat->Id}/butir", [
            'Kode' => 'TEKANAN',
            'Pertanyaan' => 'Tekanan keluaran (bar)',
            'TipeJawaban' => 'Angka',
            'Satuan' => 'bar',
            'NilaiMinimum' => 6,
            'NilaiMaksimum' => 8,
            'Wajib' => true,
        ])->assertSessionDoesntHaveErrors();

        $this->tetapkanKonteks($this->a['organisasi']);

        return [
            $templat,
            ButirTemplatDaftarPeriksa::query()->where('TemplatDaftarPeriksaId', $templat->Id)->where('Kode', 'OLI')->sole(),
            ButirTemplatDaftarPeriksa::query()->where('TemplatDaftarPeriksaId', $templat->Id)->where('Kode', 'TEKANAN')->sole(),
        ];
    }

    private function buatRencana(TemplatDaftarPeriksa $templat): RencanaPemeliharaan
    {
        $this->actingAs($this->a['manajer'])->post('/preventif-inspeksi/rencana-pemeliharaan', [
            'Kode' => 'PM-KMP-BLN',
            'Nama' => 'Preventif Bulanan Kompresor',
            'TemplatDaftarPeriksaId' => $templat->Id,
            'Prioritas' => 'Tinggi',
            'IntervalNilai' => 1,
            'IntervalSatuan' => 'Bulan',
            'BuatPerintahKerjaHariSebelum' => 7,
        ])->assertSessionDoesntHaveErrors()->assertRedirect();

        $this->tetapkanKonteks($this->a['organisasi']);

        return RencanaPemeliharaan::query()->where('Kode', 'PM-KMP-BLN')->sole();
    }

    private function daftarkanAset(RencanaPemeliharaan $rencana, Aset $aset, string $tanggalBerikutnya): RencanaPemeliharaanAset
    {
        $this->actingAs($this->a['manajer'])->post("/preventif-inspeksi/rencana-pemeliharaan/{$rencana->Id}/aset", [
            'AsetId' => $aset->Id,
            'TanggalMulai' => '2026-09-21',
            'TanggalBerikutnya' => $tanggalBerikutnya,
        ])->assertSessionDoesntHaveErrors();

        $this->tetapkanKonteks($this->a['organisasi']);

        return RencanaPemeliharaanAset::query()->where('RencanaPemeliharaanId', $rencana->Id)->where('AsetId', $aset->Id)->sole();
    }

    private function jalankanPenjadwal(): TestResponse
    {
        return $this->actingAs($this->a['manajer'])
            ->post('/preventif-inspeksi/rencana-pemeliharaan/jalankan-scheduler')
            ->assertSessionDoesntHaveErrors()
            ->assertRedirect();
    }

    private function tugaskanDanMulai(PerintahKerja $perintahKerja): void
    {
        $this->majukan(5);
        $this->actingAs($this->a['manajer'])->post("/pemeliharaan/perintah-kerja/{$perintahKerja->Id}/penugasan", [
            'PenggunaIds' => [$this->a['teknisi']->Id],
            'PeranTugas' => 'Ketua',
            'GantiPenugasanAktif' => false,
        ])->assertSessionDoesntHaveErrors();

        $this->tetapkanKonteks($this->a['organisasi']);
        $penugasan = $perintahKerja->penugasan()->where('PenggunaId', $this->a['teknisi']->Id)->sole();

        $this->majukan(1);
        $this->actingAs($this->a['teknisi'])->post("/pemeliharaan/perintah-kerja/{$perintahKerja->Id}/penugasan/{$penugasan->Id}/respons", [
            'Respons' => 'Terima',
        ])->assertSessionDoesntHaveErrors();

        $this->ubahStatus($this->a['teknisi'], $perintahKerja, 'Dikerjakan', null, 1);
    }

    private function isiDaftarPeriksa(
        PelaksanaanDaftarPeriksa $daftarPeriksa,
        ButirTemplatDaftarPeriksa $butirOli,
        ButirTemplatDaftarPeriksa $butirTekanan,
    ): TestResponse {
        $this->majukan(5);

        return $this->actingAs($this->a['teknisi'])->put("/preventif-inspeksi/pelaksanaan-daftar-periksa/{$daftarPeriksa->Id}/jawaban", [
            'jawaban' => [
                ['ButirTemplatDaftarPeriksaId' => $butirOli->Id, 'NilaiBoolean' => true],
                ['ButirTemplatDaftarPeriksaId' => $butirTekanan->Id, 'NilaiAngka' => 9.5, 'Catatan' => 'Regulator perlu disetel ulang.'],
            ],
        ]);
    }

    private function finalisasiDaftarPeriksa(PelaksanaanDaftarPeriksa $daftarPeriksa): TestResponse
    {
        $this->majukan(3);

        return $this->actingAs($this->a['teknisi'])->post("/preventif-inspeksi/pelaksanaan-daftar-periksa/{$daftarPeriksa->Id}/finalisasi", [
            'catatan' => 'Tekanan keluaran di atas batas, regulator disetel.',
        ]);
    }

    private function tutupPerintahKerja(PerintahKerja $perintahKerja): void
    {
        $this->ubahStatus($this->a['teknisi'], $perintahKerja, 'MenungguVerifikasi', 'Preventif selesai sesuai daftar periksa.');
        $this->ubahStatus($this->a['manajer'], $perintahKerja, 'Selesai');
        $this->ubahStatus($this->a['manajer'], $perintahKerja, 'Ditutup');
    }

    private function ubahStatus(Pengguna $pelaku, PerintahKerja $perintahKerja, string $status, ?string $ringkasan = null, int $menit = 5): void
    {
        $this->majukan($menit);
        $this->tetapkanKonteks($this->a['organisasi']);
        $perintahKerja->refresh();

        $this->actingAs($pelaku)->put("/pemeliharaan/perintah-kerja/{$perintahKerja->Id}/status", [
            'Status' => $status,
            'Catatan' => "Berpindah ke {$status}.",
            'RingkasanPenyelesaian' => $ringkasan,
            'Versi' => $perintahKerja->Versi,
        ])->assertSessionDoesntHaveErrors()->assertRedirect();

        $this->tetapkanKonteks($this->a['organisasi']);
        $this->assertSame($status, $perintahKerja->fresh()?->Status);
    }

    /** @return array{organisasi: Organisasi, manajer: Pengguna, teknisi: Pengguna, aset: Aset, asetKedua: Aset} */
    private function semaiOrganisasiA(): array
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-PM-A', 'Nama' => 'Rumah Sakit Preventif A']);
        $manajer = $this->buatPengguna($organisasi, 'manajer', ['Pemeliharaan.Kelola', 'PerintahKerja.Kelola']);
        $teknisi = $this->buatPengguna($organisasi, 'teknisi');

        $this->tetapkanKonteks($organisasi);
        NomorDokumen::create([
            'JenisDokumen' => 'PerintahKerja',
            'Awalan' => 'WO',
            'FormatNomor' => '{Awalan}-{Tahun}-{Nomor:4}',
            'ResetPeriode' => 'Tahunan',
        ]);
        $lokasi = Lokasi::create(['Kode' => 'LOK-UTL', 'Nama' => 'Ruang Utilitas', 'ZonaWaktu' => 'Asia/Jakarta', 'Status' => 'Aktif']);
        $kategori = KategoriAset::create(['Kode' => 'KAT-UTL', 'Nama' => 'Utilitas Medis']);
        $aset = Aset::create([
            'KategoriAsetId' => $kategori->Id,
            'KodeAset' => 'AST-KMP-01',
            'Nama' => 'Kompresor Udara Medis 1',
            'Status' => StatusAset::Aktif->value,
            'LokasiId' => $lokasi->Id,
        ]);
        $asetKedua = Aset::create([
            'KategoriAsetId' => $kategori->Id,
            'KodeAset' => 'AST-KMP-02',
            'Nama' => 'Kompresor Udara Medis 2',
            'Status' => StatusAset::Aktif->value,
            'LokasiId' => $lokasi->Id,
        ]);

        return compact('organisasi', 'manajer', 'teknisi', 'aset', 'asetKedua');
    }

    /** @return array{organisasi: Organisasi, asetPlan: RencanaPemeliharaanAset} */
    private function semaiOrganisasiBDenganRencanaJatuhTempo(): array
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-PM-B', 'Nama' => 'Rumah Sakit Preventif B']);
        $this->tetapkanKonteks($organisasi);
        NomorDokumen::create([
            'JenisDokumen' => 'PerintahKerja',
            'Awalan' => 'WOB',
            'FormatNomor' => '{Awalan}-{Tahun}-{Nomor:4}',
            'ResetPeriode' => 'Tahunan',
        ]);
        $kategori = KategoriAset::create(['Kode' => 'KAT-UTL', 'Nama' => 'Utilitas Medis']);
        $aset = Aset::create([
            'KategoriAsetId' => $kategori->Id,
            'KodeAset' => 'AST-KMP-01',
            'Nama' => 'Kompresor Organisasi B',
            'Status' => StatusAset::Aktif->value,
        ]);
        $rencana = RencanaPemeliharaan::create([
            'OrganisasiId' => $organisasi->Id,
            'Kode' => 'PM-KMP-BLN',
            'Nama' => 'Preventif Bulanan Organisasi B',
            'Jenis' => 'Preventif',
            'Prioritas' => 'Normal',
            'StrategiJadwal' => 'Interval',
            'IntervalNilai' => 1,
            'IntervalSatuan' => 'Bulan',
            'BuatPerintahKerjaHariSebelum' => 7,
            'Aktif' => true,
        ]);
        $asetPlan = RencanaPemeliharaanAset::create([
            'OrganisasiId' => $organisasi->Id,
            'RencanaPemeliharaanId' => $rencana->Id,
            'AsetId' => $aset->Id,
            'TanggalMulai' => '2026-08-24',
            'TanggalBerikutnya' => '2026-09-24',
            'Aktif' => true,
        ]);

        return compact('organisasi', 'asetPlan');
    }

    /** @param list<string> $izin */
    private function buatPengguna(Organisasi $organisasi, string $sebutan, array $izin = []): Pengguna
    {
        $pengguna = Pengguna::create([
            'OrganisasiId' => $organisasi->Id,
            'Nama' => ucfirst($sebutan).' '.$organisasi->Kode,
            'Email' => strtolower("{$sebutan}.{$organisasi->Kode}@amanpoll.test"),
            'KataSandi' => 'rahasia',
            'Status' => 'Aktif',
        ]);

        if ($izin !== []) {
            $this->tetapkanKonteks($organisasi);
            $peran = Peran::create(['Kode' => 'PERAN-'.strtoupper($sebutan), 'Nama' => 'Peran '.ucfirst($sebutan)]);
            foreach ($izin as $kode) {
                $modelIzin = Izin::firstOrCreate(['Kode' => $kode], ['Nama' => $kode, 'Modul' => 'Pemeliharaan']);
                PeranIzin::create(['PeranId' => $peran->Id, 'IzinId' => $modelIzin->Id]);
            }
            PenggunaPeran::create(['PenggunaId' => $pengguna->Id, 'PeranId' => $peran->Id]);
        }

        return $pengguna;
    }

    private function majukan(int $menit): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::now()->addMinutes($menit));
    }

    private function tetapkanKonteks(Organisasi $organisasi): void
    {
        app(KonteksOrganisasi::class)->tetapkan($organisasi->Id);
    }
}
