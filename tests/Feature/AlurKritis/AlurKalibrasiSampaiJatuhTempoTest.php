<?php

declare(strict_types=1);

namespace Tests\Feature\AlurKritis;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Aset\Domain\Enums\StatusAset;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Aset\Infrastructure\Persistence\Models\KategoriAset;
use App\Domain\Kalibrasi\Infrastructure\Persistence\Models\HasilTitikUkurKalibrasi;
use App\Domain\Kalibrasi\Infrastructure\Persistence\Models\JenisKalibrasi;
use App\Domain\Kalibrasi\Infrastructure\Persistence\Models\PelaksanaanKalibrasi;
use App\Domain\Kalibrasi\Infrastructure\Persistence\Models\RencanaKalibrasi;
use App\Domain\Kalibrasi\Infrastructure\Persistence\Models\TitikUkurKalibrasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Izin;
use App\Domain\Platform\Infrastructure\Persistence\Models\NomorDokumen;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenggunaPeran;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Domain\Platform\Infrastructure\Persistence\Models\PeranIzin;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Alur kritis FASE 26.03: kalibrasi -> hasil -> sertifikat -> jatuh tempo berikutnya.
 *
 * Lewat rute HTTP: jenis dan titik ukur standar, rencana tahunan yang sudah
 * mendekati jatuh tempo, pelaksanaan, pengukuran ulang setelah penyetelan,
 * finalisasi sertifikat, dan lampiran berkasnya. Yang dijaga di tengah jalan:
 * evaluasi lolos/gagal per titik, jatuh tempo berikutnya dihitung dari
 * tanggal kalibrasi, dasbor berpindah dari "segera jatuh tempo" ke "valid",
 * dan riwayat yang sudah disahkan tidak dapat dihapus.
 */
final class AlurKalibrasiSampaiJatuhTempoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Senin 21 September 2026 pukul 10:00 WIB.
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-09-21 03:00:00', 'UTC'));
        Storage::fake('local');
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_kalibrasi_dilaksanakan_hasilnya_disahkan_bersertifikat_dan_jatuh_tempo_berikutnya_diperbarui(): void
    {
        $a = $this->semaiOrganisasiA();
        $b = $this->semaiOrganisasiB();
        $manajer = $a['manajer'];

        // 1. Jenis kalibrasi dan dua titik ukur standar.
        $this->actingAs($manajer)->post('/kalibrasi/jenis', [
            'Kode' => 'JK-TENSI',
            'Nama' => 'Kalibrasi Tensimeter Digital',
            'Aktif' => true,
        ])->assertSessionDoesntHaveErrors()->assertRedirect();
        $this->tetapkanKonteks($a['organisasi']);
        $jenis = JenisKalibrasi::query()->where('Kode', 'JK-TENSI')->sole();

        foreach ([['Titik 100 mmHg', 100, 1], ['Titik 200 mmHg', 200, 2]] as [$nama, $referensi, $urutan]) {
            $this->actingAs($manajer)->post("/kalibrasi/jenis/{$jenis->Id}/titik-ukur", [
                'Nama' => $nama,
                'Satuan' => 'mmHg',
                'NilaiReferensi' => $referensi,
                'ToleransiMinus' => 3,
                'ToleransiPlus' => 3,
                'Urutan' => $urutan,
            ])->assertSessionDoesntHaveErrors();
        }
        $this->tetapkanKonteks($a['organisasi']);
        $this->assertSame(2, TitikUkurKalibrasi::query()->where('JenisKalibrasiId', $jenis->Id)->count());

        // 2. Rencana tahunan sejak 1 Oktober 2025: jatuh tempo 1 Oktober 2026, sepuluh hari lagi.
        $this->actingAs($manajer)->post('/kalibrasi/rencana', [
            'AsetId' => $a['aset']->Id,
            'JenisKalibrasiId' => $jenis->Id,
            'IntervalHari' => 365,
            'TanggalMulai' => '2025-10-01',
            'PeringatanHariSebelum' => 30,
        ])->assertSessionDoesntHaveErrors();
        $this->tetapkanKonteks($a['organisasi']);
        $rencana = RencanaKalibrasi::query()->where('AsetId', $a['aset']->Id)->sole();
        $this->assertSame('2026-10-01', $rencana->TanggalBerikutnya->toDateString());
        $this->assertStatistikDasbor($manajer, valid: 0, segeraJatuhTempo: 1, terlambat: 0);

        // 3. Rencana milik organisasi lain tidak dapat dijadikan dasar pelaksanaan.
        $this->actingAs($manajer)->post('/kalibrasi/pelaksanaan', [
            'AsetId' => $a['aset']->Id,
            'RencanaKalibrasiId' => $b['rencana']->Id,
            'TanggalKalibrasi' => '2026-09-21',
        ])->assertSessionHasErrors('RencanaKalibrasiId');
        $this->tetapkanKonteks($a['organisasi']);
        $this->assertSame(0, PelaksanaanKalibrasi::query()->count());

        // 4. Pelaksanaan dijadwalkan dari rencana: jenis diwarisi, titik ukur standar disalin.
        $this->actingAs($manajer)->post('/kalibrasi/pelaksanaan', [
            'AsetId' => $a['aset']->Id,
            'RencanaKalibrasiId' => $rencana->Id,
            'TanggalKalibrasi' => '2026-09-21',
            'DilaksanakanOleh' => $a['teknisi']->Id,
            'Laboratorium' => 'Laboratorium Kalibrasi Internal',
        ])->assertSessionDoesntHaveErrors()->assertRedirect();
        $this->tetapkanKonteks($a['organisasi']);
        $pelaksanaan = PelaksanaanKalibrasi::query()->sole();
        $this->assertSame('KAL-2026-0001', $pelaksanaan->Nomor);
        $this->assertSame($jenis->Id, $pelaksanaan->JenisKalibrasiId);
        $this->assertSame('Terjadwal', $pelaksanaan->Hasil);
        $this->assertNull($pelaksanaan->DiverifikasiPada);

        $titik = $this->hasilTitik($pelaksanaan);
        $this->assertSame(['Titik 100 mmHg', 'Titik 200 mmHg'], array_keys($titik));
        $this->assertSame('BelumDiuji', $titik['Titik 100 mmHg']->Hasil);
        $this->assertSame(100.0, (float) $titik['Titik 100 mmHg']->NilaiReferensi);

        // 5. Pengukuran pertama oleh pelaksana: 104 di luar toleransi 100 ± 3, 201,5 di dalam.
        $this->simpanPengukuran($a['teknisi'], $pelaksanaan, $titik, 104.0, 201.5);
        $this->tetapkanKonteks($a['organisasi']);
        $titik = $this->hasilTitik($pelaksanaan);
        $this->assertSame('Gagal', $titik['Titik 100 mmHg']->Hasil);
        $this->assertSame(4.0, (float) $titik['Titik 100 mmHg']->Koreksi);
        $this->assertSame('Lolos', $titik['Titik 200 mmHg']->Hasil);
        $this->assertSame(1.5, (float) $titik['Titik 200 mmHg']->Koreksi);

        // 6. Setelah penyetelan, pengukuran ulang memperbarui baris yang sama, tidak menambah baris.
        $this->simpanPengukuran($a['teknisi'], $pelaksanaan, $titik, 101.0, 201.5);
        $this->tetapkanKonteks($a['organisasi']);
        $titik = $this->hasilTitik($pelaksanaan);
        $this->assertCount(2, $titik);
        $this->assertSame('Lolos', $titik['Titik 100 mmHg']->Hasil);
        $this->assertSame(1.0, (float) $titik['Titik 100 mmHg']->Koreksi);

        // 7. Finalisasi dan pengesahan sertifikat: jatuh tempo berikutnya = tanggal kalibrasi + 365 hari.
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-09-21 07:30:00', 'UTC'));
        $this->actingAs($manajer)->post("/kalibrasi/pelaksanaan/{$pelaksanaan->Id}/finalisasi", [
            'Hasil' => 'Lolos',
            'NomorSertifikat' => 'SERT/KAL/2026/0917',
            'TanggalKalibrasi' => '2026-09-21',
            'Laboratorium' => 'Laboratorium Kalibrasi Internal',
        ])->assertSessionDoesntHaveErrors()->assertRedirect();

        $this->tetapkanKonteks($a['organisasi']);
        $pelaksanaan->refresh();
        $this->assertSame('Lolos', $pelaksanaan->Hasil);
        $this->assertSame('SERT/KAL/2026/0917', $pelaksanaan->NomorSertifikat);
        $this->assertSame($manajer->Id, $pelaksanaan->DiverifikasiOleh);
        $this->assertSame('2026-09-21 14:30:00', $pelaksanaan->DiverifikasiPada?->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s'));
        $this->assertSame('2027-09-21', $pelaksanaan->TanggalBerlakuSampai?->toDateString());
        $this->assertSame('2027-09-21', $rencana->fresh()?->TanggalBerikutnya->toDateString());
        $this->assertSame(1, DB::table('CatatanAudit')
            ->where('OrganisasiId', $a['organisasi']->Id)
            ->where('Aksi', 'PelaksanaanKalibrasi.Difinalisasi')
            ->where('EntitasId', $pelaksanaan->Id)
            ->count());

        // 8. Berkas sertifikat dilampirkan pada pelaksanaan.
        $this->actingAs($manajer)->post('/kolaborasi/berkas', [
            'Berkas' => UploadedFile::fake()->create('sertifikat-kalibrasi.pdf', 120, 'application/pdf'),
            'JenisEntitas' => 'PelaksanaanKalibrasi',
            'EntitasId' => $pelaksanaan->Id,
            'Kategori' => 'Sertifikat',
        ])->assertSessionDoesntHaveErrors();
        $this->assertDatabaseHas('LampiranEntitas', [
            'JenisEntitas' => 'PelaksanaanKalibrasi',
            'EntitasId' => $pelaksanaan->Id,
            'Kategori' => 'Sertifikat',
            'DibuatOleh' => $manajer->Id,
        ]);

        // 9. Dasbor kini melihat aset ini valid sampai tahun depan.
        $this->assertStatistikDasbor($manajer, valid: 1, segeraJatuhTempo: 0, terlambat: 0);

        // 10. Riwayat yang sudah disahkan tidak dapat dihapus.
        $this->actingAs($manajer)->delete("/kalibrasi/pelaksanaan/{$pelaksanaan->Id}")->assertStatus(422);
        $this->tetapkanKonteks($a['organisasi']);
        $this->assertTrue(PelaksanaanKalibrasi::query()->whereKey($pelaksanaan->Id)->exists());
        $this->assertSame(2, HasilTitikUkurKalibrasi::query()->where('PelaksanaanKalibrasiId', $pelaksanaan->Id)->count());

        // 11. Rencana organisasi kedua tidak ikut bergeser.
        $this->tetapkanKonteks($b['organisasi']);
        $this->assertSame('2026-10-05', $b['rencana']->fresh()?->TanggalBerikutnya->toDateString());
        $this->assertSame(0, PelaksanaanKalibrasi::query()->count());
    }

    private function assertStatistikDasbor(Pengguna $pengguna, int $valid, int $segeraJatuhTempo, int $terlambat): void
    {
        $this->actingAs($pengguna)->get('/kalibrasi')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $halaman) => $halaman
                ->component('Kalibrasi/Index')
                ->where('statistik.total', 1)
                ->where('statistik.valid', $valid)
                ->where('statistik.segeraJatuhTempo', $segeraJatuhTempo)
                ->where('statistik.terlambat', $terlambat));
    }

    /**
     * Muatan meniru EditorTitikUkur: setiap baris membawa referensi dan
     * toleransi yang ditampilkan kepada pelaksana, bukan hanya nilai terukur.
     *
     * @param  array<string, HasilTitikUkurKalibrasi>  $titik
     */
    private function simpanPengukuran(Pengguna $pelaksana, PelaksanaanKalibrasi $pelaksanaan, array $titik, float $terukur100, float $terukur200): void
    {
        $baris = [];
        foreach (['Titik 100 mmHg' => $terukur100, 'Titik 200 mmHg' => $terukur200] as $nama => $terukur) {
            $baris[] = [
                'Id' => $titik[$nama]->Id,
                'TitikUkurKalibrasiId' => $titik[$nama]->TitikUkurKalibrasiId,
                'NamaTitik' => $nama,
                'NilaiReferensi' => $titik[$nama]->NilaiReferensi,
                'ToleransiMinus' => 3,
                'ToleransiPlus' => 3,
                'NilaiTerukur' => $terukur,
                'Satuan' => 'mmHg',
            ];
        }

        $this->actingAs($pelaksana)->put("/kalibrasi/pelaksanaan/{$pelaksanaan->Id}/hasil-titik-ukur", [
            'hasil' => $baris,
        ])->assertSessionDoesntHaveErrors()->assertRedirect();
    }

    /** @return array<string, HasilTitikUkurKalibrasi> */
    private function hasilTitik(PelaksanaanKalibrasi $pelaksanaan): array
    {
        return HasilTitikUkurKalibrasi::query()
            ->where('PelaksanaanKalibrasiId', $pelaksanaan->Id)
            ->orderBy('NamaTitik')
            ->get()
            ->keyBy('NamaTitik')
            ->all();
    }

    /** @return array{organisasi: Organisasi, manajer: Pengguna, teknisi: Pengguna, aset: Aset} */
    private function semaiOrganisasiA(): array
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-KAL-A', 'Nama' => 'Rumah Sakit Kalibrasi A']);
        $manajer = $this->buatPengguna($organisasi, 'manajer', ['Kalibrasi.Kelola']);
        $teknisi = $this->buatPengguna($organisasi, 'teknisi');

        $this->tetapkanKonteks($organisasi);
        NomorDokumen::create([
            'JenisDokumen' => 'Kalibrasi',
            'Awalan' => 'KAL',
            'FormatNomor' => '{Awalan}-{Tahun}-{Nomor:4}',
            'ResetPeriode' => 'Tahunan',
        ]);
        $aset = $this->buatAset('Tensimeter Digital Ruang IGD');

        return compact('organisasi', 'manajer', 'teknisi', 'aset');
    }

    /** @return array{organisasi: Organisasi, rencana: RencanaKalibrasi} */
    private function semaiOrganisasiB(): array
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-KAL-B', 'Nama' => 'Rumah Sakit Kalibrasi B']);
        $this->tetapkanKonteks($organisasi);
        $aset = $this->buatAset('Tensimeter Organisasi B');
        $rencana = RencanaKalibrasi::create([
            'OrganisasiId' => $organisasi->Id,
            'AsetId' => $aset->Id,
            'IntervalHari' => 365,
            'TanggalMulai' => '2025-10-05',
            'TanggalBerikutnya' => '2026-10-05',
            'PeringatanHariSebelum' => 30,
            'Aktif' => true,
        ]);

        return compact('organisasi', 'rencana');
    }

    private function buatAset(string $nama): Aset
    {
        $kategori = KategoriAset::create(['Kode' => 'KAT-ALKES', 'Nama' => 'Alat Kesehatan']);

        return Aset::create([
            'KategoriAsetId' => $kategori->Id,
            'KodeAset' => 'AST-TENSI-01',
            'Nama' => $nama,
            'Status' => StatusAset::Aktif->value,
        ]);
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
                $modelIzin = Izin::firstOrCreate(['Kode' => $kode], ['Nama' => $kode, 'Modul' => 'Kalibrasi']);
                PeranIzin::create(['PeranId' => $peran->Id, 'IzinId' => $modelIzin->Id]);
            }
            PenggunaPeran::create(['PenggunaId' => $pengguna->Id, 'PeranId' => $peran->Id]);
        }

        return $pengguna;
    }

    private function tetapkanKonteks(Organisasi $organisasi): void
    {
        app(KonteksOrganisasi::class)->tetapkan($organisasi->Id);
    }
}
