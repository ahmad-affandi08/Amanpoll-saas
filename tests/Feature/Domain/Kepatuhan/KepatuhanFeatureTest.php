<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Kepatuhan;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Aset\Infrastructure\Persistence\Models\KategoriAset;
use App\Domain\Kepatuhan\Application\Actions\KelolaKepatuhanAset;
use App\Domain\Kepatuhan\Application\Actions\KelolaSertifikasiAset;
use App\Domain\Kepatuhan\Application\Actions\KelolaStandarKepatuhan;
use App\Domain\Kepatuhan\Application\Services\LayananKepatuhan;
use App\Domain\Kepatuhan\Infrastructure\Persistence\Models\KepatuhanAset;
use App\Domain\Kepatuhan\Infrastructure\Persistence\Models\SertifikasiAset;
use App\Domain\Kepatuhan\Infrastructure\Persistence\Models\StandarKepatuhan;
use App\Domain\Platform\Infrastructure\Persistence\Models\Izin;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenggunaPeran;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Domain\Platform\Infrastructure\Persistence\Models\PeranIzin;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * FASE 18 — standar kepatuhan, persyaratan, kepatuhan aset, dan sertifikasi.
 */
final class KepatuhanFeatureTest extends TestCase
{
    use DatabaseTransactions;

    public function test_gate_18_tidak_ada_standar_regulator_bawaan_di_dalam_kode(): void
    {
        // Seeder platform hanya berisi izin dan data sistem, bukan katalog standar.
        $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\IzinSeeder'])->assertSuccessful();
        $this->assertSame(0, DB::table('StandarKepatuhan')->count());

        // Tidak ada berkas kode yang memuat daftar standar regulator bawaan.
        $berkas = array_merge(
            glob(app_path('Domain/Kepatuhan/**/*.php')) ?: [],
            glob(database_path('seeders/*.php')) ?: [],
        );
        foreach ($berkas as $satuBerkas) {
            $isi = (string) file_get_contents($satuBerkas);
            $this->assertStringNotContainsString('ISO 55001', $isi, basename($satuBerkas));
            $this->assertStringNotContainsString('OSHA', $isi, basename($satuBerkas));
        }

        // Organisasi baru memulai tanpa satu pun standar.
        $konteks = $this->siapkanKonteks();
        $this->assertSame(0, StandarKepatuhan::query()->where('OrganisasiId', $konteks['organisasi']->Id)->count());
    }

    public function test_18_01_standar_kepatuhan_dibuat_diubah_dan_dijaga_saat_dipakai(): void
    {
        $konteks = $this->siapkanKonteks();
        $aksi = app(KelolaStandarKepatuhan::class);

        $standar = $aksi->buat($this->dataStandar());
        $this->assertTrue($standar->Aktif);
        $this->assertSame('2024', $standar->VersiStandar);

        $diubah = $aksi->ubah($standar, array_merge($this->dataStandar(), ['VersiStandar' => '2026']));
        $this->assertSame('2026', $diubah->VersiStandar);

        $aksi->tambahPersyaratan($standar, ['Kode' => 'P-01', 'Nama' => 'Uji tahanan isolasi']);
        $this->assertThrows(fn () => $aksi->hapus($standar->refresh()), AturanBisnisDilanggar::class);

        // Kode persyaratan unik dalam satu standar.
        $this->assertThrows(
            fn () => $aksi->tambahPersyaratan($standar, ['Kode' => 'P-01', 'Nama' => 'Duplikat']),
            AturanBisnisDilanggar::class,
        );
    }

    public function test_18_02_persyaratan_tidak_dapat_dihapus_setelah_ditugaskan_ke_aset(): void
    {
        $konteks = $this->siapkanKonteks();
        $aksi = app(KelolaStandarKepatuhan::class);
        $standar = $aksi->buat($this->dataStandar());
        $persyaratan = $aksi->tambahPersyaratan($standar, [
            'Kode' => 'P-01',
            'Nama' => 'Uji tahanan isolasi',
            'BuktiYangDiperlukan' => 'Berita acara uji',
            'IntervalHari' => 365,
        ]);

        app(KelolaKepatuhanAset::class)->tugaskanStandar($konteks['aset'], $standar->refresh());

        $this->assertThrows(
            fn () => $aksi->hapusPersyaratan($standar, $persyaratan->refresh()),
            AturanBisnisDilanggar::class,
        );
    }

    public function test_18_03_penugasan_standar_idempoten_dan_menolak_standar_kosong(): void
    {
        $konteks = $this->siapkanKonteks();
        $kelolaStandar = app(KelolaStandarKepatuhan::class);
        $aksi = app(KelolaKepatuhanAset::class);
        $standar = $kelolaStandar->buat($this->dataStandar());

        // Standar tanpa persyaratan tidak dapat ditugaskan.
        $this->assertThrows(
            fn () => $aksi->tugaskanStandar($konteks['aset'], $standar),
            AturanBisnisDilanggar::class,
        );

        $kelolaStandar->tambahPersyaratan($standar, ['Kode' => 'P-01', 'Nama' => 'Uji isolasi']);
        $kelolaStandar->tambahPersyaratan($standar, ['Kode' => 'P-02', 'Nama' => 'Uji pembumian']);

        $pertama = $aksi->tugaskanStandar($konteks['aset'], $standar->refresh());
        $this->assertSame(['ditambahkan' => 2, 'dilewati' => 0], $pertama);

        // Penugasan ulang tidak menggandakan kewajiban.
        $kedua = $aksi->tugaskanStandar($konteks['aset'], $standar->refresh());
        $this->assertSame(['ditambahkan' => 0, 'dilewati' => 2], $kedua);
        $this->assertSame(2, KepatuhanAset::query()->where('AsetId', $konteks['aset']->Id)->count());
    }

    public function test_18_03_pemeriksaan_menghitung_masa_berlaku_dari_interval_persyaratan(): void
    {
        $konteks = $this->siapkanKonteks();
        $kepatuhan = $this->buatKewajiban($konteks, intervalHari: 365);
        $aksi = app(KelolaKepatuhanAset::class);

        $hasil = $aksi->catatPemeriksaan($kepatuhan, [
            'Status' => KepatuhanAset::STATUS_PATUH,
            'TanggalPemeriksaan' => '2026-01-10',
            'Catatan' => 'Hasil uji memenuhi ambang.',
        ], $konteks['pengguna']->Id);

        $this->assertSame(KepatuhanAset::STATUS_PATUH, $hasil->Status);
        $this->assertSame('2027-01-10', $hasil->BerlakuSampai?->toDateString());
        $this->assertSame($konteks['pengguna']->Id, $hasil->DiperiksaOleh);

        // Masa berlaku manual tidak boleh mendahului tanggal pemeriksaan.
        $this->assertThrows(
            fn () => $aksi->catatPemeriksaan($hasil->refresh(), [
                'Status' => KepatuhanAset::STATUS_PATUH,
                'TanggalPemeriksaan' => '2026-02-01',
                'BerlakuSampai' => '2026-01-01',
            ], $konteks['pengguna']->Id),
            AturanBisnisDilanggar::class,
        );

        // Pemeriksaan bertanggal masa depan ditolak.
        $this->assertThrows(
            fn () => $aksi->catatPemeriksaan($hasil->refresh(), [
                'Status' => KepatuhanAset::STATUS_PATUH,
                'TanggalPemeriksaan' => CarbonImmutable::today()->addDay()->toDateString(),
            ], $konteks['pengguna']->Id),
            AturanBisnisDilanggar::class,
        );
    }

    public function test_18_03_persyaratan_tanpa_interval_tidak_memiliki_masa_berlaku(): void
    {
        $konteks = $this->siapkanKonteks();
        $kepatuhan = $this->buatKewajiban($konteks, intervalHari: null);

        $hasil = app(KelolaKepatuhanAset::class)->catatPemeriksaan($kepatuhan, [
            'Status' => KepatuhanAset::STATUS_PATUH,
            'TanggalPemeriksaan' => '2026-01-10',
        ], $konteks['pengguna']->Id);

        $this->assertNull($hasil->BerlakuSampai);
        $this->assertSame(KepatuhanAset::STATUS_PATUH, app(LayananKepatuhan::class)->statusEfektif($hasil));
    }

    public function test_18_04_sertifikat_diterbitkan_diperpanjang_dan_dicabut(): void
    {
        $konteks = $this->siapkanKonteks();
        $aksi = app(KelolaSertifikasiAset::class);

        $sertifikat = $aksi->terbitkan($konteks['aset'], [
            'JenisSertifikasi' => 'Laik operasi',
            'NomorSertifikat' => 'SRT-001',
            'Penerbit' => 'Lembaga Uji Mandiri',
            'TerbitPada' => '2026-01-01',
            'BerlakuSampai' => '2026-12-31',
        ]);
        $this->assertSame(SertifikasiAset::STATUS_AKTIF, $sertifikat->Status);

        // Masa berlaku mendahului tanggal terbit ditolak.
        $this->assertThrows(
            fn () => $aksi->terbitkan($konteks['aset'], [
                'JenisSertifikasi' => 'Salah periode',
                'TerbitPada' => '2026-06-01',
                'BerlakuSampai' => '2026-05-01',
            ]),
            AturanBisnisDilanggar::class,
        );

        $dicabut = $aksi->cabut($sertifikat, 'Ditemukan ketidaksesuaian data uji.');
        $this->assertSame(SertifikasiAset::STATUS_DICABUT, $dicabut->Status);
        $this->assertThrows(
            fn () => $aksi->ubah($dicabut->refresh(), ['JenisSertifikasi' => 'Coba ubah']),
            AturanBisnisDilanggar::class,
        );
    }

    public function test_18_04_sertifikat_kedaluwarsa_ditutup_dan_aktif_lagi_setelah_diperpanjang(): void
    {
        $konteks = $this->siapkanKonteks();
        $sertifikat = app(KelolaSertifikasiAset::class)->terbitkan($konteks['aset'], [
            'JenisSertifikasi' => 'Laik operasi',
            'NomorSertifikat' => 'SRT-002',
            'TerbitPada' => '2026-01-01',
            'BerlakuSampai' => '2026-04-01',
        ]);

        $hasil = app(LayananKepatuhan::class)
            ->kirimPeringatan($konteks['organisasi']->Id, CarbonImmutable::parse('2026-04-02'));

        $this->assertSame(1, $hasil['sertifikatKedaluwarsa']);
        $this->assertSame(SertifikasiAset::STATUS_KEDALUWARSA, $sertifikat->refresh()->Status);
        $this->assertSame(1, $this->jumlahNotifikasi($konteks, 'SertifikasiAset', 'Sertifikasi.Kedaluwarsa'));

        $diperpanjang = app(KelolaSertifikasiAset::class)->ubah($sertifikat->refresh(), [
            'JenisSertifikasi' => 'Laik operasi',
            'NomorSertifikat' => 'SRT-002',
            'TerbitPada' => '2026-01-01',
            'BerlakuSampai' => CarbonImmutable::today()->addYear()->toDateString(),
        ]);
        $this->assertSame(SertifikasiAset::STATUS_AKTIF, $diperpanjang->Status);
    }

    public function test_18_04_peringatan_dikirim_pada_ambang_dan_tidak_digandakan(): void
    {
        $konteks = $this->siapkanKonteks();
        $kepatuhan = $this->buatKewajiban($konteks, intervalHari: 90);
        app(KelolaKepatuhanAset::class)->catatPemeriksaan($kepatuhan, [
            'Status' => KepatuhanAset::STATUS_PATUH,
            'TanggalPemeriksaan' => '2026-01-01',
            'BerlakuSampai' => '2026-04-01',
        ], $konteks['pengguna']->Id);

        $layanan = app(LayananKepatuhan::class);

        // H-90 tepat memicu satu peringatan.
        $hasil = $layanan->kirimPeringatan($konteks['organisasi']->Id, CarbonImmutable::parse('2026-01-01'));
        $this->assertSame(1, $hasil['kepatuhanAkanBerakhir']);
        $this->assertSame(1, $this->jumlahNotifikasi($konteks, 'KepatuhanAset', 'Kepatuhan.AkanBerakhir.H90'));

        // Pemanggilan ulang pada siklus yang sama tidak menambah notifikasi.
        $ulang = $layanan->kirimPeringatan($konteks['organisasi']->Id, CarbonImmutable::parse('2026-01-01'));
        $this->assertSame(1, $ulang['dilewati']);
        $this->assertSame(1, $this->jumlahNotifikasi($konteks, 'KepatuhanAset', 'Kepatuhan.AkanBerakhir.H90'));

        // Hari bukan ambang tidak memicu apa pun.
        $sepi = $layanan->kirimPeringatan($konteks['organisasi']->Id, CarbonImmutable::parse('2026-01-20'));
        $this->assertSame(0, $sepi['kepatuhanAkanBerakhir']);

        // Setelah lewat, status kewajiban ditandai kedaluwarsa.
        $layanan->kirimPeringatan($konteks['organisasi']->Id, CarbonImmutable::parse('2026-04-05'));
        $this->assertSame(KepatuhanAset::STATUS_KEDALUWARSA, $kepatuhan->refresh()->Status);
    }

    public function test_18_04_ringkasan_dasbor_menghitung_kepatuhan_dan_sertifikat(): void
    {
        $konteks = $this->siapkanKonteks();
        $kepatuhan = $this->buatKewajiban($konteks, intervalHari: 365);
        app(KelolaKepatuhanAset::class)->catatPemeriksaan($kepatuhan, [
            'Status' => KepatuhanAset::STATUS_PATUH,
            'TanggalPemeriksaan' => CarbonImmutable::today()->toDateString(),
        ], $konteks['pengguna']->Id);

        app(KelolaSertifikasiAset::class)->terbitkan($konteks['aset'], [
            'JenisSertifikasi' => 'Laik operasi',
            'BerlakuSampai' => CarbonImmutable::today()->addDays(20)->toDateString(),
        ]);

        $ringkasan = app(LayananKepatuhan::class)->ringkasan($konteks['organisasi']->Id);

        $this->assertSame(1, $ringkasan['totalKewajiban']);
        $this->assertSame(1, $ringkasan['patuh']);
        $this->assertSame(100.0, $ringkasan['persentaseKepatuhan']);
        $this->assertSame(1, $ringkasan['sertifikatAkanBerakhir']);
        $this->assertSame(0, $ringkasan['sertifikatKedaluwarsa']);
    }

    public function test_endpoint_kepatuhan_menegakkan_izin_dan_isolasi_tenant(): void
    {
        $konteks = $this->siapkanKonteks();

        $tanpaIzin = $this->buatPengguna($konteks['organisasi'], []);
        $this->actingAs($tanpaIzin)->get(route('kepatuhan.index'))->assertForbidden();

        $organisasiLain = $this->buatOrganisasi('LAIN');
        app(KonteksOrganisasi::class)->tetapkan($organisasiLain->Id);
        $standarLain = StandarKepatuhan::create([
            'Kode' => 'STD-LAIN',
            'Nama' => 'Standar Tenant Lain',
            'Aktif' => true,
        ]);

        app(KonteksOrganisasi::class)->tetapkan($konteks['organisasi']->Id);
        $this->actingAs($konteks['pengguna'])
            ->get(route('kepatuhan.standar.show', $standarLain->Id))
            ->assertNotFound();
    }

    public function test_halaman_operasional_fase_18_dapat_dirender(): void
    {
        $konteks = $this->siapkanKonteks();
        $kepatuhan = $this->buatKewajiban($konteks, intervalHari: 180);
        $standar = $kepatuhan->persyaratanKepatuhan->standarKepatuhan;
        app(KelolaSertifikasiAset::class)->terbitkan($konteks['aset'], [
            'JenisSertifikasi' => 'Laik operasi',
            'BerlakuSampai' => CarbonImmutable::today()->addYear()->toDateString(),
        ]);

        $this->actingAs($konteks['pengguna']);
        $this->get(route('kepatuhan.index'))->assertOk();
        $this->get(route('kepatuhan.standar.show', $standar))->assertOk();
        $this->get(route('kepatuhan.sertifikasi.index'))->assertOk();
    }

    /**
     * @return array{organisasi: Organisasi, pengguna: Pengguna, aset: Aset}
     */
    private function siapkanKonteks(): array
    {
        $organisasi = $this->buatOrganisasi('KPT');
        app(KonteksOrganisasi::class)->tetapkan($organisasi->Id);
        $pengguna = $this->buatPengguna($organisasi, ['Kepatuhan.Kelola']);
        $this->actingAs($pengguna);

        $kategori = KategoriAset::create(['Kode' => 'KAT-'.uniqid(), 'Nama' => 'Panel Listrik']);
        $aset = Aset::create([
            'KategoriAsetId' => $kategori->Id,
            'KodeAset' => 'AST-'.uniqid(),
            'Nama' => 'Panel Utama',
            'Status' => Aset::STATUS_AKTIF,
            'Kondisi' => Aset::KONDISI_BAIK,
            'TingkatKritis' => Aset::KRITIS_NORMAL,
        ]);

        return ['organisasi' => $organisasi, 'pengguna' => $pengguna, 'aset' => $aset];
    }

    /**
     * @return array<string, mixed>
     */
    private function dataStandar(): array
    {
        return [
            'Kode' => 'STD-INTERNAL',
            'Nama' => 'Standar Keselamatan Internal',
            'Penerbit' => 'Komite Mutu Internal',
            'VersiStandar' => '2024',
            'JenisIndustri' => 'Fasilitas',
            'Deskripsi' => 'Disusun sendiri oleh organisasi.',
            'Aktif' => true,
        ];
    }

    /**
     * @param  array<string, mixed>  $konteks
     */
    private function buatKewajiban(array $konteks, ?int $intervalHari): KepatuhanAset
    {
        $kelolaStandar = app(KelolaStandarKepatuhan::class);
        $standar = $kelolaStandar->buat(array_merge($this->dataStandar(), ['Kode' => 'STD-'.uniqid()]));
        $kelolaStandar->tambahPersyaratan($standar, [
            'Kode' => 'P-01',
            'Nama' => 'Uji tahanan isolasi',
            'BuktiYangDiperlukan' => 'Berita acara uji',
            'IntervalHari' => $intervalHari,
        ]);
        app(KelolaKepatuhanAset::class)->tugaskanStandar($konteks['aset'], $standar->refresh());

        return KepatuhanAset::query()
            ->with('persyaratanKepatuhan.standarKepatuhan')
            ->where('AsetId', $konteks['aset']->Id)
            ->latest('DibuatPada')
            ->firstOrFail();
    }

    /**
     * @param  array<string, mixed>  $konteks
     */
    private function jumlahNotifikasi(array $konteks, string $jenisEntitas, string $jenisPeristiwa): int
    {
        return DB::table('Notifikasi')
            ->where('OrganisasiId', $konteks['organisasi']->Id)
            ->where('JenisEntitas', $jenisEntitas)
            ->where('JenisPeristiwa', $jenisPeristiwa)
            ->count();
    }

    private function buatOrganisasi(string $kode): Organisasi
    {
        return Organisasi::create([
            'Kode' => $kode.'-'.uniqid(),
            'Nama' => 'Organisasi '.$kode.' '.uniqid(),
            'Status' => 'Aktif',
        ]);
    }

    /**
     * @param  list<string>  $izin
     */
    private function buatPengguna(Organisasi $organisasi, array $izin): Pengguna
    {
        app(KonteksOrganisasi::class)->tetapkan($organisasi->Id);
        $pengguna = Pengguna::create([
            'OrganisasiId' => $organisasi->Id,
            'Nama' => 'Pengguna '.uniqid(),
            'Email' => 'pengguna.'.uniqid().'@amanpoll.test',
            'KataSandi' => 'password',
            'Status' => 'Aktif',
        ]);

        if ($izin === []) {
            return $pengguna;
        }

        $peran = Peran::create(['Kode' => 'PERAN-'.uniqid(), 'Nama' => 'Peran Kepatuhan']);
        foreach ($izin as $kodeIzin) {
            $modelIzin = Izin::firstOrCreate(['Kode' => $kodeIzin], ['Nama' => $kodeIzin, 'Modul' => 'Kepatuhan']);
            PeranIzin::create(['PeranId' => $peran->Id, 'IzinId' => $modelIzin->Id]);
        }
        PenggunaPeran::create(['OrganisasiId' => $organisasi->Id, 'PenggunaId' => $pengguna->Id, 'PeranId' => $peran->Id]);

        return $pengguna;
    }
}
