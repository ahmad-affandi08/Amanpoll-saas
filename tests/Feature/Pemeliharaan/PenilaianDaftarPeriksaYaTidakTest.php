<?php

declare(strict_types=1);

namespace Tests\Feature\Pemeliharaan;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\PreventifInspeksi\Application\Actions\KelolaButirDaftarPeriksa;
use App\Domain\PreventifInspeksi\Application\Actions\KelolaPelaksanaanDaftarPeriksa;
use App\Domain\PreventifInspeksi\Application\Actions\KelolaTemplatDaftarPeriksa;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\ButirTemplatDaftarPeriksa;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\JawabanDaftarPeriksa;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\PelaksanaanDaftarPeriksa;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\TemplatDaftarPeriksa;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Butir Ya/Tidak dinilai terhadap jawaban pemicu temuannya. Pertanyaan negatif
 * ("Ada kebocoran?") memicu temuan pada "Ya", sehingga "Tidak" justru sesuai.
 * Pemicu tersimpan dalam dua rupa: teks "Ya"/"Tidak" dari layar templat dan
 * boolean dari data lama; keduanya harus dibaca sama.
 */
final class PenilaianDaftarPeriksaYaTidakTest extends TestCase
{
    use RefreshDatabase;

    private Pengguna $teknisi;

    private TemplatDaftarPeriksa $templat;

    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow('2026-05-04 03:00:00');

        $organisasi = Organisasi::create(['Kode' => 'ORG-YT', 'Nama' => 'Organisasi Checklist']);
        $this->teknisi = Pengguna::create([
            'OrganisasiId' => $organisasi->Id,
            'Nama' => 'Teknisi Lapangan',
            'Email' => 'teknisi.'.uniqid().'@amanpoll.test',
            'KataSandi' => 'rahasia',
            'Status' => 'Aktif',
        ]);
        app(KonteksOrganisasi::class)->tetapkan($organisasi->Id);

        $this->templat = app(KelolaTemplatDaftarPeriksa::class)->buat(['Kode' => 'DP-GEN', 'Nama' => 'PM Genset'], $this->teknisi->Id);
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_pertanyaan_negatif_dengan_pemicu_ya_menilai_tidak_sebagai_sesuai(): void
    {
        $kebocoran = $this->butir('Ada kebocoran oli?', 'Ya');

        $this->assertSame(true, $this->nilai($kebocoran, false), 'Tidak ada kebocoran: sesuai.');
        $this->assertSame(false, $this->nilai($kebocoran, true), 'Ada kebocoran: temuan.');
    }

    public function test_pertanyaan_positif_dengan_pemicu_tidak_menilai_ya_sebagai_sesuai(): void
    {
        $oli = $this->butir('Level oli cukup?', 'Tidak');

        $this->assertSame(true, $this->nilai($oli, true));
        $this->assertSame(false, $this->nilai($oli, false));
    }

    public function test_pemicu_boolean_dari_data_lama_dibaca_sama_dengan_teks(): void
    {
        $bocor = $this->butir('Ada rembesan air pendingin?', true);
        $bersih = $this->butir('Filter udara bersih?', false);

        $this->assertSame(true, $this->nilai($bocor, false));
        $this->assertSame(false, $this->nilai($bocor, true));
        $this->assertSame(true, $this->nilai($bersih, true));
        $this->assertSame(false, $this->nilai($bersih, false));
    }

    public function test_jawaban_berupa_teks_atau_angka_formulir_dan_tanpa_pemicu(): void
    {
        $tanpaPemicu = $this->butir('Panel bersih?', null);
        $kebocoran = $this->butir('Ada kebocoran?', 'Ya');

        $this->assertSame(true, $this->nilai($tanpaPemicu, '1'));
        $this->assertSame(false, $this->nilai($tanpaPemicu, '0'), '"0" dari formulir berarti Tidak, bukan Ya.');
        $this->assertSame(true, $this->nilai($kebocoran, 'false'));
        $this->assertFalse($this->jawabanTersimpan($kebocoran)->NilaiBoolean, 'Teks "false" tersimpan sebagai Tidak.');
    }

    public function test_skor_finalisasi_tidak_menghukum_jawaban_benar_pada_pertanyaan_negatif(): void
    {
        $kebocoran = $this->butir('Ada kebocoran oli?', 'Ya');
        $oli = $this->butir('Level oli cukup?', 'Tidak');
        $pelaksanaan = $this->pelaksanaanDariPerintahKerja();
        $aksi = app(KelolaPelaksanaanDaftarPeriksa::class);

        $aksi->simpanJawaban($pelaksanaan, [
            ['ButirTemplatDaftarPeriksaId' => $kebocoran->Id, 'NilaiBoolean' => false],
            ['ButirTemplatDaftarPeriksaId' => $oli->Id, 'NilaiBoolean' => true],
        ], $this->teknisi->Id);

        $this->assertEquals(100, $aksi->finalisasi($pelaksanaan, null, $this->teknisi->Id)->Skor);
    }

    public function test_pelaksanaan_dari_perintah_kerja_mencatat_pelaksana_dan_waktu_mulai_saat_jawaban_pertama(): void
    {
        $oli = $this->butir('Level oli cukup?', 'Tidak');
        $pelaksanaan = $this->pelaksanaanDariPerintahKerja();
        $aksi = app(KelolaPelaksanaanDaftarPeriksa::class);

        CarbonImmutable::setTestNow('2026-05-04 07:30:00');
        $aksi->simpanJawaban($pelaksanaan, [['ButirTemplatDaftarPeriksaId' => $oli->Id, 'NilaiBoolean' => true]], $this->teknisi->Id);

        $pelaksanaan->refresh();
        $this->assertSame($this->teknisi->Id, $pelaksanaan->DilaksanakanOleh);
        $this->assertSame('2026-05-04 07:30:00', $pelaksanaan->MulaiPada?->format('Y-m-d H:i:s'));

        $rekan = Pengguna::create([
            'OrganisasiId' => $this->teknisi->OrganisasiId,
            'Nama' => 'Rekan Teknisi',
            'Email' => 'rekan.'.uniqid().'@amanpoll.test',
            'KataSandi' => 'rahasia',
            'Status' => 'Aktif',
        ]);
        CarbonImmutable::setTestNow('2026-05-04 09:00:00');
        $aksi->simpanJawaban($pelaksanaan, [['ButirTemplatDaftarPeriksaId' => $oli->Id, 'NilaiBoolean' => false]], $rekan->Id);

        $pelaksanaan->refresh();
        $this->assertSame($this->teknisi->Id, $pelaksanaan->DilaksanakanOleh, 'Pelaksana pertama tidak ditimpa.');
        $this->assertSame('2026-05-04 07:30:00', $pelaksanaan->MulaiPada?->format('Y-m-d H:i:s'));
    }

    private function butir(string $pertanyaan, string|bool|null $pemicu): ButirTemplatDaftarPeriksa
    {
        return app(KelolaButirDaftarPeriksa::class)->simpan($this->templat, [
            'Pertanyaan' => $pertanyaan,
            'TipeJawaban' => 'YaTidak',
            'Wajib' => true,
            'MemicuTemuanJika' => $pemicu === null ? null : ['nilai' => $pemicu],
        ]);
    }

    private function nilai(ButirTemplatDaftarPeriksa $butir, bool|string $jawaban): ?bool
    {
        $pelaksanaan = $this->pelaksanaanDariPerintahKerja();
        app(KelolaPelaksanaanDaftarPeriksa::class)->simpanJawaban($pelaksanaan, [
            ['ButirTemplatDaftarPeriksaId' => $butir->Id, 'NilaiBoolean' => $jawaban],
        ], $this->teknisi->Id);

        return $this->jawabanTersimpan($butir, $pelaksanaan)->Sesuai;
    }

    private function jawabanTersimpan(ButirTemplatDaftarPeriksa $butir, ?PelaksanaanDaftarPeriksa $pelaksanaan = null): JawabanDaftarPeriksa
    {
        return JawabanDaftarPeriksa::query()
            ->where('ButirTemplatDaftarPeriksaId', $butir->Id)
            ->when($pelaksanaan !== null, fn ($q) => $q->where('PelaksanaanDaftarPeriksaId', $pelaksanaan?->Id))
            ->latest('DijawabPada')
            ->firstOrFail();
    }

    /** Bentuk yang dibuat penjadwal preventif: tanpa pelaksana dan tanpa waktu mulai. */
    private function pelaksanaanDariPerintahKerja(): PelaksanaanDaftarPeriksa
    {
        return PelaksanaanDaftarPeriksa::create([
            'TemplatDaftarPeriksaId' => $this->templat->Id,
            'Status' => 'Draft',
        ]);
    }
}
