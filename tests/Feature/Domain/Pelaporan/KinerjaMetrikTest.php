<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Pelaporan;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Aset\Domain\Enums\StatusAset;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Aset\Infrastructure\Persistence\Models\KategoriAset;
use App\Domain\Pelaporan\Application\Services\LayananMetrik;
use App\Domain\Pelaporan\Application\Services\RegistriKpi;
use App\Domain\Pelaporan\Domain\ValueObjects\FilterMetrik;
use App\Domain\Pelaporan\Domain\ValueObjects\RumusKeandalan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\KategoriKeluhan;
use App\Domain\Platform\Infrastructure\Persistence\Models\Izin;
use App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenggunaPeran;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Domain\Platform\Infrastructure\Persistence\Models\PeranIzin;
use Carbon\CarbonImmutable;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * FASE 45 temuan #4: KPI dasbor dihitung lebih hemat tanpa mengubah angkanya.
 *
 * - Keandalan dan waktu respons kini diagregasi di SQL; hasilnya dibandingkan
 *   dengan salinan algoritme PHP lama atas dataset semai yang sengaja memuat
 *   kasus tepi (mikrodetik, sesi terbuka, sesi melewati akhir rentang, durasi
 *   negatif, batas bulan pada zona bermusim panas).
 * - Empat KPI keandalan yang diminta bersama berbagi satu ringkasan.
 * - Cache hasil terisolasi per organisasi, lingkup akses, dan filter, dan habis
 *   sesuai TTL.
 */
final class KinerjaMetrikTest extends KasusPelaporan
{
    private const KPI_KEANDALAN = ['downtime.total_jam', 'downtime.ketersediaan', 'keandalan.mttr', 'keandalan.mtbf'];

    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow('2026-06-15 09:00:00');
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    /** @return array<string, array{string, string, string}> */
    public static function rentangDanZona(): array
    {
        return [
            'Jakarta lintas bulan' => ['2026-01-15', '2026-04-10', 'Asia/Jakarta'],
            'Jayapura satu bulan' => ['2026-03-01', '2026-03-31', 'Asia/Jayapura'],
            'Berlin melewati awal musim panas' => ['2026-02-20', '2026-04-05', 'Europe/Berlin'],
        ];
    }

    #[DataProvider('rentangDanZona')]
    public function test_kpi_keandalan_identik_dengan_perhitungan_php_lama(string $dari, string $sampai, string $zona): void
    {
        $this->semaiWaktuHenti();
        $filter = FilterMetrik::dariArray(['Dari' => $dari, 'Sampai' => $sampai], $zona);

        $rujukan = $this->keandalanVersiPhpLama($filter);
        $registri = app(RegistriKpi::class);

        foreach (self::KPI_KEANDALAN as $kunci) {
            $this->assertSame(
                $rujukan[$kunci],
                $registri->untuk($kunci)->hitung($kunci, $filter)->keArray(),
                "KPI {$kunci} berbeda dari perhitungan PHP lama.",
            );
        }

        // Pembanding tidak boleh kosong: dataset semai memang menyentuh rentang ini.
        $this->assertGreaterThan(0.0, $rujukan['downtime.total_jam']['Nilai']);
    }

    public function test_waktu_respons_keluhan_identik_dengan_perhitungan_php_lama(): void
    {
        $lokasi = $this->buatLokasi('Poli');
        $kategori = $this->buatKategoriKeluhan();
        $dasar = CarbonImmutable::parse('2026-06-10 03:00:00.250000', 'UTC');

        // Pecahan detik membuat menit terpotong berbeda bila presisinya hilang (59,8 detik => 0 menit).
        foreach ([[0, 59.8], [1, 61.2], [2, 125.999], [3, 3600.5], [4, -90.0], [5, 7.0]] as [$indeks, $detik]) {
            $dilaporkan = $dasar->addHours($indeks);
            $this->sisipkanKeluhan($lokasi, $kategori, $dilaporkan, $dilaporkan->addMicroseconds((int) round($detik * 1_000_000)));
        }
        $this->sisipkanKeluhan($lokasi, $kategori, $dasar->addHours(9), null);

        $filter = FilterMetrik::dariArray(['Dari' => '2026-06-01', 'Sampai' => '2026-06-15'], 'Asia/Jakarta');

        $baris = DB::table('Keluhan')
            ->where('OrganisasiId', $this->organisasi->Id)
            ->whereNotNull('DiresponsPada')
            ->whereBetween('DilaporkanPada', [$filter->dari, $filter->sampai])
            ->get(['DilaporkanPada', 'DiresponsPada']);
        $totalMenit = $baris->sum(fn (object $satu): int => max(0, (int) CarbonImmutable::parse($satu->DilaporkanPada, 'UTC')
            ->diffInMinutes(CarbonImmutable::parse($satu->DiresponsPada, 'UTC'))));

        $hasil = app(RegistriKpi::class)->untuk('keluhan.waktu_respons')->hitung('keluhan.waktu_respons', $filter);

        $this->assertSame(6, $baris->count());
        $this->assertSame(round($totalMenit / 6, 0), $hasil->nilai);
        $this->assertSame(['AdaData' => true, 'Penyebut' => 6, 'TotalMenit' => $totalMenit], $hasil->konteks);
        // 0 + 1 + 2 + 60 + 0 + 0: bukti bahwa detik pecahan dan selisih negatif ikut diuji.
        $this->assertSame(63, $totalMenit);
    }

    public function test_kpi_keandalan_yang_diminta_bersama_berbagi_satu_ringkasan(): void
    {
        config(['amanpoll.pelaporan.cache_kpi_detik' => 0]);
        $this->semaiWaktuHenti();
        $pengguna = $this->buatPengguna();
        $this->actingAs($pengguna, 'web');
        $filter = FilterMetrik::dariArray(['Dari' => '2026-01-15', 'Sampai' => '2026-04-10'], 'Asia/Jakarta');

        $kueri = $this->hitungKueri('WaktuHentiAset', fn () => app(LayananMetrik::class)->hitungBanyak(self::KPI_KEANDALAN, $filter, $pengguna));
        $sendiri = $this->hitungKueri('WaktuHentiAset', fn () => app(LayananMetrik::class)->hitungBanyak(['downtime.total_jam'], $filter, $pengguna));

        // Satu kueri ringkasan per jenis/bulan ditambah satu hitungan aset berbeda, untuk keempat KPI sekaligus.
        $this->assertSame(2, $kueri);
        $this->assertSame($sendiri, $kueri);
    }

    public function test_cache_kpi_tidak_bocor_antar_organisasi(): void
    {
        $penggunaA = $this->buatPengguna(['Keluhan.Kelola']);
        $this->sisipkanKeluhan($this->buatLokasi('A'), $this->buatKategoriKeluhan(), CarbonImmutable::now()->subDay(), null);
        $filter = FilterMetrik::bawaan('Asia/Jakarta');

        $this->actingAs($penggunaA, 'web');
        $this->assertSame(1.0, $this->nilai('keluhan.terbuka', $filter, $penggunaA));

        $organisasiB = Organisasi::create(['Kode' => 'ORG-LAP-B-'.uniqid(), 'Nama' => 'Organisasi B']);
        app(KonteksOrganisasi::class)->tetapkan($organisasiB->Id);
        $penggunaB = $this->buatPengguna(['Keluhan.Kelola'], $organisasiB);
        $kategoriB = KategoriKeluhan::create(['OrganisasiId' => $organisasiB->Id, 'Kode' => 'KAT-B-'.uniqid(), 'Nama' => 'Kategori B']);
        $lokasiB = Lokasi::create(['OrganisasiId' => $organisasiB->Id, 'Kode' => 'LOK-B-'.uniqid(), 'Nama' => 'Gedung B', 'Status' => 'Aktif']);
        foreach ([1, 2, 3] as $jam) {
            $this->sisipkanKeluhan($lokasiB, $kategoriB, CarbonImmutable::now()->subHours($jam), null, $organisasiB);
        }

        $this->actingAs($penggunaB, 'web');
        $this->assertSame(3.0, $this->nilai('keluhan.terbuka', $filter, $penggunaB));

        app(KonteksOrganisasi::class)->tetapkan($this->organisasi->Id);
        $this->actingAs($penggunaA, 'web');
        $this->assertSame(1.0, $this->nilai('keluhan.terbuka', $filter, $penggunaA));
    }

    public function test_cache_kpi_tidak_bocor_antar_lingkup_pengguna(): void
    {
        $poli = $this->buatLokasi('Poli');
        $igd = $this->buatLokasi('IGD');
        $kategori = $this->buatKategoriKeluhan();
        $this->sisipkanKeluhan($poli, $kategori, CarbonImmutable::now()->subHours(2), null);
        $this->sisipkanKeluhan($igd, $kategori, CarbonImmutable::now()->subHours(3), null);
        $this->sisipkanKeluhan($igd, $kategori, CarbonImmutable::now()->subHours(4), null);
        $filter = FilterMetrik::bawaan('Asia/Jakarta');

        $seluruhRumahSakit = $this->buatPengguna(['Keluhan.Kelola']);
        $hanyaPoli = $this->buatPenggunaBerlingkup($poli);

        $this->actingAs($seluruhRumahSakit, 'web');
        $this->assertSame(3.0, $this->nilai('keluhan.terbuka', $filter, $seluruhRumahSakit));

        $this->actingAs($hanyaPoli, 'web');
        $this->assertSame(1.0, $this->nilai('keluhan.terbuka', $filter, $hanyaPoli));

        $this->actingAs($seluruhRumahSakit, 'web');
        $this->assertSame(3.0, $this->nilai('keluhan.terbuka', $filter, $seluruhRumahSakit));
    }

    public function test_cache_kpi_memuat_filter_dan_habis_sesuai_ttl(): void
    {
        config(['amanpoll.pelaporan.cache_kpi_detik' => 45]);
        $pengguna = $this->buatPengguna(['Keluhan.Kelola']);
        $this->actingAs($pengguna, 'web');
        $poli = $this->buatLokasi('Poli');
        $igd = $this->buatLokasi('IGD');
        $kategori = $this->buatKategoriKeluhan();
        $this->sisipkanKeluhan($poli, $kategori, CarbonImmutable::now()->subHour(), null);
        $filter = FilterMetrik::bawaan('Asia/Jakarta');

        $this->assertSame(1.0, $this->nilai('keluhan.masuk', $filter, $pengguna));

        $this->sisipkanKeluhan($poli, $kategori, CarbonImmutable::now()->subMinutes(10), null);
        $this->sisipkanKeluhan($igd, $kategori, CarbonImmutable::now()->subMinutes(5), null);

        // Masih di dalam TTL: angka lama dari cache.
        $this->travel(44)->seconds();
        $this->assertSame(1.0, $this->nilai('keluhan.masuk', $filter, $pengguna));

        // Filter lain tidak pernah memakai entri filter sebelumnya.
        $hanyaIgd = FilterMetrik::dariArray(['LokasiId' => [$igd->Id]], 'Asia/Jakarta');
        $this->assertSame(1.0, $this->nilai('keluhan.masuk', $hanyaIgd, $pengguna));
        $rentangLain = FilterMetrik::dariArray(['Dari' => '2026-06-01', 'Sampai' => '2026-06-15'], 'Asia/Jakarta');
        $this->assertSame(3.0, $this->nilai('keluhan.masuk', $rentangLain, $pengguna));

        // Lewat TTL: dihitung ulang.
        $this->travel(2)->seconds();
        $this->assertSame(3.0, $this->nilai('keluhan.masuk', $filter, $pengguna));
    }

    public function test_cache_dimatikan_dengan_nol(): void
    {
        config(['amanpoll.pelaporan.cache_kpi_detik' => 0]);
        $pengguna = $this->buatPengguna(['Keluhan.Kelola']);
        $this->actingAs($pengguna, 'web');
        $lokasi = $this->buatLokasi('Poli');
        $kategori = $this->buatKategoriKeluhan();
        $filter = FilterMetrik::bawaan('Asia/Jakarta');

        $this->assertSame(0.0, $this->nilai('keluhan.terbuka', $filter, $pengguna));
        $this->sisipkanKeluhan($lokasi, $kategori, CarbonImmutable::now()->subHour(), null);
        $this->assertSame(1.0, $this->nilai('keluhan.terbuka', $filter, $pengguna));
    }

    private function nilai(string $kunci, FilterMetrik $filter, Pengguna $pengguna): float
    {
        $hasil = app(LayananMetrik::class)->hitungBanyak([$kunci], $filter, $pengguna);
        $this->assertArrayHasKey($kunci, $hasil);

        return (float) $hasil[$kunci]['Nilai'];
    }

    private function hitungKueri(string $tabel, callable $aksi): int
    {
        $jumlah = 0;
        DB::listen(function (QueryExecuted $kueri) use ($tabel, &$jumlah): void {
            if (str_contains($kueri->sql, "from `{$tabel}`")) {
                $jumlah++;
            }
        });

        $aksi();
        $sebelum = $jumlah;
        $jumlah = 0;

        return $sebelum;
    }

    /**
     * Sesi waktu henti dengan kasus tepi: mikrodetik, sesi terbuka, sesi yang
     * melewati akhir rentang, durasi negatif, dan sesi tepat di sekitar batas
     * bulan dalam zona Berlin (29 Maret 2026 masuk musim panas).
     */
    private function semaiWaktuHenti(): void
    {
        $asetSatu = $this->buatAset('AST-1');
        $asetDua = $this->buatAset('AST-2');
        $asetTiga = $this->buatAset('AST-3');

        $sesi = [
            [$asetSatu, '2026-01-20 10:00:00.500000', '2026-01-20 10:59:59.900000', 'TidakTerencana'],
            [$asetSatu, '2026-01-31 16:59:30.000000', '2026-02-01 02:00:00.000000', 'TidakTerencana'],
            [$asetDua, '2026-01-31 23:30:00.000000', '2026-02-01 01:00:00.250000', 'Terencana'],
            [$asetDua, '2026-02-28 22:45:00.000000', '2026-02-28 23:30:00.000000', 'TidakTerencana'],
            [$asetTiga, '2026-02-28 23:15:00.000000', null, 'TidakTerencana'],
            [$asetTiga, '2026-03-10 08:00:00.000000', '2026-03-10 07:00:00.000000', 'TidakTerencana'],
            [$asetSatu, '2026-03-31 21:59:00.000000', '2026-03-31 22:30:00.000000', 'Terencana'],
            [$asetSatu, '2026-03-31 22:10:00.000000', '2026-04-02 00:00:00.000000', 'TidakTerencana'],
            [$asetDua, '2026-04-03 12:00:59.750000', '2026-06-01 00:00:00.000000', 'TidakTerencana'],
            [$asetTiga, '2026-04-08 12:00:59.500000', null, 'TidakTerencana'],
            [$asetTiga, '2026-04-04 20:00:00.000000', null, 'Terencana'],
            [$asetDua, '2026-04-09 23:59:59.999999', '2026-04-10 00:00:59.999998', 'TidakTerencana'],
            [$asetTiga, '2025-12-01 00:00:00.000000', '2026-02-01 00:00:00.000000', 'TidakTerencana'],
        ];

        foreach ($sesi as [$aset, $mulai, $selesai, $jenis]) {
            DB::table('WaktuHentiAset')->insert([
                'Id' => strtolower((string) Str::ulid()),
                'OrganisasiId' => $this->organisasi->Id,
                'AsetId' => $aset->Id,
                'MulaiPada' => $mulai,
                'SelesaiPada' => $selesai,
                'Jenis' => $jenis,
                'Alasan' => 'Uji',
                'DibuatPada' => '2026-06-01 00:00:00',
            ]);
        }
    }

    /**
     * Salinan setia QueryKeandalan sebelum FASE 45: seluruh sesi ditarik ke
     * PHP lalu dihitung per KPI. Hanya pengurutan PerJenisJam yang
     * dinormalkan, karena versi lama mengikuti urutan baris dari basis data.
     *
     * @return array<string, array<string, mixed>>
     */
    private function keandalanVersiPhpLama(FilterMetrik $filter): array
    {
        $sesi = DB::table('WaktuHentiAset')
            ->where('OrganisasiId', $this->organisasi->Id)
            ->whereBetween('MulaiPada', [$filter->dari, $filter->sampai])
            ->get(['AsetId', 'Jenis', 'MulaiPada', 'SelesaiPada'])
            ->map(fn (object $satu): array => [
                'AsetId' => (string) $satu->AsetId,
                'Jenis' => (string) $satu->Jenis,
                'MulaiPada' => CarbonImmutable::parse($satu->MulaiPada, 'UTC'),
                'SelesaiPada' => $satu->SelesaiPada === null ? null : CarbonImmutable::parse($satu->SelesaiPada, 'UTC'),
            ]);

        $menitEfektif = function (array $satu) use ($filter): int {
            $selesai = $satu['SelesaiPada'] ?? $filter->sampai;
            if ($selesai->greaterThan($filter->sampai)) {
                $selesai = $filter->sampai;
            }

            return max(0, (int) $satu['MulaiPada']->diffInMinutes($selesai));
        };
        $menitRentang = (int) $filter->dari->diffInMinutes($filter->sampai);

        $perJenis = [];
        $perBulan = [];
        foreach ($sesi as $satu) {
            $menit = $menitEfektif($satu);
            $perJenis[$satu['Jenis']] = ($perJenis[$satu['Jenis']] ?? 0) + $menit;
            $bulan = $satu['MulaiPada']->setTimezone($filter->zona)->format('Y-m');
            $perBulan[$bulan] = ($perBulan[$bulan] ?? 0) + $menit;
        }
        ksort($perJenis);

        $deret = [];
        $bulan = CarbonImmutable::parse($filter->tanggalDari())->startOfMonth();
        while ($bulan->lessThanOrEqualTo(CarbonImmutable::parse($filter->tanggalSampai()))) {
            $deret[] = ['Label' => $bulan->format('Y-m'), 'Nilai' => (float) RumusKeandalan::totalJam($perBulan[$bulan->format('Y-m')] ?? 0)];
            $bulan = $bulan->addMonth();
        }

        $totalMenit = array_sum($perJenis);
        $menitTersedia = RumusKeandalan::menitOperasional($sesi->pluck('AsetId')->unique()->count(), $menitRentang);
        $menitAktif = RumusKeandalan::menitAktif($menitTersedia, $totalMenit);

        $kegagalan = $sesi->where('Jenis', 'TidakTerencana');
        $selesai = $kegagalan->filter(fn (array $satu): bool => $satu['SelesaiPada'] !== null);
        $menitSelesai = $selesai->sum($menitEfektif);
        $menitGagal = $kegagalan->sum($menitEfektif);
        $menitOperasionalGagal = RumusKeandalan::menitOperasional($kegagalan->pluck('AsetId')->unique()->count(), $menitRentang);

        return [
            'downtime.total_jam' => [
                'Nilai' => RumusKeandalan::totalJam($totalMenit),
                'Rincian' => $deret,
                'Konteks' => ['PerJenisJam' => array_map(RumusKeandalan::totalJam(...), $perJenis)],
            ],
            'downtime.ketersediaan' => [
                'Nilai' => round($menitAktif / $menitTersedia * 100, 1),
                'Rincian' => [
                    ['Label' => 'Tersedia', 'Nilai' => RumusKeandalan::totalJam((int) $menitAktif)],
                    ['Label' => 'Downtime', 'Nilai' => RumusKeandalan::totalJam($totalMenit)],
                ],
                'Konteks' => ['Pembilang' => $menitAktif, 'Penyebut' => $menitTersedia, 'AdaData' => true],
            ],
            'keandalan.mttr' => [
                'Nilai' => RumusKeandalan::mttr($menitSelesai, $selesai->count()),
                'Rincian' => [],
                'Konteks' => ['AdaData' => true, 'Penyebut' => $selesai->count(), 'TotalMenit' => $menitSelesai],
            ],
            'keandalan.mtbf' => [
                'Nilai' => RumusKeandalan::mtbf($menitOperasionalGagal, $menitGagal, $kegagalan->count()),
                'Rincian' => [],
                'Konteks' => [
                    'AdaData' => true,
                    'Penyebut' => $kegagalan->count(),
                    'MenitOperasional' => $menitOperasionalGagal,
                    'MenitDowntime' => $menitGagal,
                ],
            ],
        ];
    }

    private function buatAset(string $kode): Aset
    {
        $kategori = KategoriAset::firstOrCreate(
            ['OrganisasiId' => $this->organisasi->Id, 'Kode' => 'KAT-KINERJA'],
            ['Nama' => 'Kategori kinerja'],
        );

        return Aset::create([
            'OrganisasiId' => $this->organisasi->Id,
            'KategoriAsetId' => $kategori->Id,
            'KodeAset' => $kode.'-'.uniqid(),
            'Nama' => 'Aset '.$kode,
            'Status' => StatusAset::Aktif->value,
        ]);
    }

    private function buatLokasi(string $nama): Lokasi
    {
        return Lokasi::create([
            'OrganisasiId' => $this->organisasi->Id,
            'Kode' => 'LOK-'.uniqid(),
            'Nama' => $nama,
            'Status' => 'Aktif',
        ]);
    }

    private function buatKategoriKeluhan(): KategoriKeluhan
    {
        return KategoriKeluhan::create([
            'OrganisasiId' => $this->organisasi->Id,
            'Kode' => 'KAT-'.uniqid(),
            'Nama' => 'Kategori uji',
        ]);
    }

    /** Lewat query builder supaya pecahan detik tersimpan apa adanya di kolom datetime(6). */
    private function sisipkanKeluhan(
        Lokasi $lokasi,
        KategoriKeluhan $kategori,
        CarbonImmutable $dilaporkan,
        ?CarbonImmutable $direspons,
        ?Organisasi $organisasi = null,
    ): void {
        DB::table('Keluhan')->insert([
            'Id' => strtolower((string) Str::ulid()),
            'OrganisasiId' => ($organisasi ?? $this->organisasi)->Id,
            'Nomor' => 'KLH-'.uniqid(),
            'KategoriKeluhanId' => $kategori->Id,
            'LokasiId' => $lokasi->Id,
            'Judul' => 'Keluhan uji',
            'Deskripsi' => 'Uji kinerja metrik.',
            'Prioritas' => 'Normal',
            'Status' => 'Baru',
            'NamaPelaporEksternal' => 'Perawat jaga',
            'DilaporkanPada' => $dilaporkan->utc()->format('Y-m-d H:i:s.u'),
            'DiresponsPada' => $direspons?->utc()->format('Y-m-d H:i:s.u'),
            'DibuatPada' => '2026-06-01 00:00:00',
            'DiperbaruiPada' => '2026-06-01 00:00:00',
        ]);
    }

    /** Pengguna yang penugasan perannya dibatasi satu ruangan (LingkupAkses). */
    private function buatPenggunaBerlingkup(Lokasi $lokasi): Pengguna
    {
        $pengguna = $this->buatPengguna();
        $peran = Peran::create([
            'OrganisasiId' => $this->organisasi->Id,
            'Kode' => 'PERAN-'.uniqid(),
            'Nama' => 'Peran ruangan',
        ]);
        $izin = Izin::firstOrCreate(['Kode' => 'Keluhan.Kelola'], ['Nama' => 'Keluhan.Kelola', 'Modul' => 'Pelaporan']);
        PeranIzin::create(['PeranId' => $peran->Id, 'IzinId' => $izin->Id]);
        PenggunaPeran::create([
            'OrganisasiId' => $this->organisasi->Id,
            'PenggunaId' => $pengguna->Id,
            'PeranId' => $peran->Id,
            'LokasiId' => $lokasi->Id,
        ]);

        return $pengguna;
    }
}
