<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Pelaporan;

use App\Domain\Pelaporan\Application\Services\PenyusunBarisLaporan;
use App\Domain\Pelaporan\Application\Services\RegistriKpi;
use App\Domain\Pelaporan\Domain\KatalogKpi;
use App\Domain\Pelaporan\Domain\ValueObjects\FilterMetrik;
use App\Domain\Pelaporan\Infrastructure\Persistence\Models\LaporanTersimpan;
use App\Domain\Pelaporan\Jobs\BuatEksporLaporan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\KategoriKeluhan;
use App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi;
use App\Shared\Infrastructure\Ekspor\FormatEkspor;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use ReflectionProperty;

/**
 * FASE 45 temuan #5: rentang KPI dibatasi di layar, divalidasi di ekspor, dan
 * deret panjang tidak lagi menjadi ribuan titik.
 */
final class BatasRentangMetrikTest extends KasusPelaporan
{
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

    public function test_tanggal_tak_terbaca_di_dasbor_kembali_ke_bawaan_bukan_galat(): void
    {
        $pengguna = $this->buatPengguna();

        foreach ([['Dari' => 'abc'], ['Dari' => '2026-02-31'], ['Sampai' => ['2026-01-01']], ['Dari' => '0001-01-01']] as $kueri) {
            $props = $this->actingAs($pengguna)
                ->get(route('dashboard', $kueri))
                ->assertOk()
                ->viewData('page')['props'];

            $this->assertSame('2026-05-17', $props['filter']['Dari'], json_encode($kueri) ?: '');
            $this->assertSame('2026-06-15', $props['filter']['Sampai']);
            $this->assertCount(1, $props['catatanRentang']);
            $this->assertStringContainsString('tidak dikenali', $props['catatanRentang'][0]);
        }
    }

    public function test_rentang_dasbor_dipotong_ke_batas_interaktif_dan_dikabarkan(): void
    {
        $pengguna = $this->buatPengguna();

        $props = $this->actingAs($pengguna)
            ->get(route('dashboard', ['Dari' => '2018-01-01', 'Sampai' => '2026-06-15']))
            ->assertOk()
            ->viewData('page')['props'];

        // 365 hari terakhir rentang yang diminta: hari terakhirnya tetap.
        $this->assertSame('2025-06-16', $props['filter']['Dari']);
        $this->assertSame('2026-06-15', $props['filter']['Sampai']);
        $this->assertCount(1, $props['catatanRentang']);
        $this->assertStringContainsString('365 hari', $props['catatanRentang'][0]);

        $dalamBatas = $this->actingAs($pengguna)
            ->get(route('dashboard', ['Dari' => '2025-06-16', 'Sampai' => '2026-06-15']))
            ->viewData('page')['props'];
        $this->assertSame('2025-06-16', $dalamBatas['filter']['Dari']);
        $this->assertSame([], $dalamBatas['catatanRentang']);
    }

    public function test_batas_interaktif_dibaca_dari_config(): void
    {
        config(['amanpoll.pelaporan.rentang_maks_hari_interaktif' => 30]);
        $pengguna = $this->buatPengguna();

        $props = $this->actingAs($pengguna)
            ->get(route('dashboard', ['Dari' => '2026-01-01', 'Sampai' => '2026-06-15']))
            ->viewData('page')['props'];

        $this->assertSame('2026-05-17', $props['filter']['Dari']);
    }

    public function test_laporan_tersimpan_berentang_panjang_dihitung_terpotong_tetapi_diekspor_penuh(): void
    {
        $pengguna = $this->buatPengguna();
        $this->actingAs($pengguna)
            ->post(route('pelaporan.laporan.store'), [
                'Nama' => 'Dua tahun',
                'Pribadi' => true,
                'Konfigurasi' => [
                    'KunciKpi' => ['perintah_kerja.selesai'],
                    'Filter' => ['Dari' => '2024-06-16', 'Sampai' => '2026-06-15'],
                ],
            ])
            ->assertSessionHasNoErrors();
        $laporan = LaporanTersimpan::query()->where('Nama', 'Dua tahun')->firstOrFail();

        $props = $this->actingAs($pengguna)
            ->get(route('pelaporan.laporan.index', ['laporan' => $laporan->Id]))
            ->assertOk()
            ->viewData('page')['props'];

        $this->assertSame('2025-06-16', $props['filter']['Dari']);
        $this->assertSame('2024-06-16', $props['filterDiminta']['Dari']);
        $this->assertSame('2026-06-15', $props['filterDiminta']['Sampai']);
        $this->assertStringContainsString('ekspor', $props['catatanRentang'][0]);
    }

    public function test_ekspor_rentang_panjang_masuk_antrean_dengan_rentang_penuh(): void
    {
        Queue::fake();
        $pengguna = $this->buatPengguna();

        $this->actingAs($pengguna)
            ->post(route('pelaporan.ekspor.store'), [
                'Judul' => 'Empat tahun',
                'Format' => FormatEkspor::Csv->value,
                'KunciKpi' => ['perintah_kerja.selesai', 'perintah_kerja.aktif'],
                'Filter' => ['Dari' => '2022-06-16', 'Sampai' => '2026-06-15'],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        Queue::assertPushed(BuatEksporLaporan::class, function (BuatEksporLaporan $job): bool {
            $filter = (new ReflectionProperty($job, 'filter'))->getValue($job);

            return is_array($filter) && $filter['Dari'] === '2022-06-16' && $filter['Sampai'] === '2026-06-15';
        });
    }

    public function test_ekspor_melewati_batas_atas_rentang_ditolak_rapi(): void
    {
        Queue::fake();
        $pengguna = $this->buatPengguna();

        $this->actingAs($pengguna)
            ->post(route('pelaporan.ekspor.store'), [
                'Judul' => 'Sejak 2018',
                'Format' => FormatEkspor::Csv->value,
                'KunciKpi' => ['perintah_kerja.selesai'],
                'Filter' => ['Dari' => '2018-01-01', 'Sampai' => '2026-06-15'],
            ])
            ->assertSessionHasErrors('Filter.Sampai');

        $this->actingAs($pengguna)
            ->post(route('pelaporan.ekspor.store'), [
                'Judul' => 'Tanggal karangan',
                'Format' => FormatEkspor::Csv->value,
                'KunciKpi' => ['perintah_kerja.selesai'],
                'Filter' => ['Dari' => 'abc'],
            ])
            ->assertSessionHasErrors('Filter.Dari');

        Queue::assertNothingPushed();
    }

    public function test_ekspor_rentang_panjang_membatasi_jumlah_kpi(): void
    {
        Queue::fake();
        config(['amanpoll.pelaporan.kpi_maks_ekspor_rentang_panjang' => 2]);
        $pengguna = $this->buatPengguna();
        $tigaKpi = ['perintah_kerja.selesai', 'perintah_kerja.aktif', 'perintah_kerja.terlambat'];

        $this->actingAs($pengguna)
            ->post(route('pelaporan.ekspor.store'), [
                'Judul' => 'Dua tahun, tiga KPI',
                'Format' => FormatEkspor::Csv->value,
                'KunciKpi' => $tigaKpi,
                'Filter' => ['Dari' => '2024-06-16', 'Sampai' => '2026-06-15'],
            ])
            ->assertSessionHasErrors('KunciKpi');
        Queue::assertNothingPushed();

        // Rentang interaktif tetap boleh memuat KPI sebanyak batas umum.
        $this->actingAs($pengguna)
            ->post(route('pelaporan.ekspor.store'), [
                'Judul' => 'Sebulan, tiga KPI',
                'Format' => FormatEkspor::Csv->value,
                'KunciKpi' => $tigaKpi,
                'Filter' => ['Dari' => '2026-05-16', 'Sampai' => '2026-06-15'],
            ])
            ->assertSessionHasNoErrors();
        Queue::assertPushed(BuatEksporLaporan::class);
    }

    public function test_ekspor_menolak_kpi_melebihi_batas_umum(): void
    {
        Queue::fake();
        config(['amanpoll.pelaporan.kpi_maks_ekspor' => 2]);
        $pengguna = $this->buatPengguna();

        $this->actingAs($pengguna)
            ->post(route('pelaporan.ekspor.store'), [
                'Judul' => 'Tiga KPI',
                'Format' => FormatEkspor::Csv->value,
                'KunciKpi' => ['perintah_kerja.selesai', 'perintah_kerja.aktif', 'perintah_kerja.terlambat'],
            ])
            ->assertSessionHasErrors('KunciKpi');

        Queue::assertNothingPushed();
    }

    public function test_laporan_tersimpan_melewati_batas_atas_rentang_ditolak(): void
    {
        $pengguna = $this->buatPengguna();

        $this->actingAs($pengguna)
            ->post(route('pelaporan.laporan.store'), [
                'Nama' => 'Terlalu panjang',
                'Pribadi' => true,
                'Konfigurasi' => [
                    'KunciKpi' => ['perintah_kerja.aktif'],
                    'Filter' => ['Dari' => '2018-01-01', 'Sampai' => '2026-06-15'],
                ],
            ])
            ->assertSessionHasErrors('Konfigurasi.Filter.Sampai');

        $this->assertFalse(LaporanTersimpan::query()->where('Nama', 'Terlalu panjang')->exists());
    }

    public function test_deret_harian_dikelompokkan_per_minggu_lalu_per_bulan_tanpa_mengubah_total(): void
    {
        $this->semaiKeluhanHarian('2023-06-01', '2026-06-15', 3);

        $kasus = [
            ['2026-05-17', '2026-06-15', 30, null],
            ['2026-01-01', '2026-06-15', 25, 'mingguan'],
            ['2023-06-16', '2026-06-15', 37, 'bulanan'],
        ];

        foreach ($kasus as [$dari, $sampai, $jumlahTitik, $kelompok]) {
            $filter = FilterMetrik::dariArray(['Dari' => $dari, 'Sampai' => $sampai], 'Asia/Jakarta');
            $hasil = app(RegistriKpi::class)->untuk('keluhan.masuk')->hitung('keluhan.masuk', $filter);

            $this->assertCount($jumlahTitik, $hasil->rincian, "{$dari} s.d. {$sampai}");
            $this->assertSame($hasil->nilai, array_sum(array_column($hasil->rincian, 'Nilai')), 'Total deret harus sama dengan nilai KPI.');
            $this->assertGreaterThan(0.0, $hasil->nilai);
            if ($kelompok === null) {
                $this->assertSame(['Label' => $dari, 'Nilai' => 3.0], $hasil->rincian[0]);
            }

            if ($kelompok === 'mingguan') {
                // Minggu pertama dipotong di awal rentang (Kamis 1 Januari) dan berakhir Minggu.
                $this->assertSame(['Label' => '2026-01-01', 'Nilai' => 12.0, 'SampaiTanggal' => '2026-01-04'], $hasil->rincian[0]);
                $this->assertSame('2026-01-05', $hasil->rincian[1]['Label']);
                $this->assertSame('2026-01-11', $hasil->rincian[1]['SampaiTanggal']);
                $this->assertSame('2026-06-15', $hasil->rincian[count($hasil->rincian) - 1]['SampaiTanggal']);
            }

            if ($kelompok === 'bulanan') {
                $this->assertSame('2023-06', $hasil->rincian[0]['Label']);
                $this->assertSame('2026-06', $hasil->rincian[count($hasil->rincian) - 1]['Label']);
            }
        }
    }

    public function test_ekspor_menyebut_rentang_minggu_pada_label_rincian(): void
    {
        $pengguna = $this->buatPengguna(['Keluhan.Kelola']);
        $this->actingAs($pengguna, 'web');
        $this->semaiKeluhanHarian('2026-01-01', '2026-01-10', 1);

        $baris = app(PenyusunBarisLaporan::class)->baris(
            ['keluhan.masuk'],
            FilterMetrik::dariArray(['Dari' => '2026-01-01', 'Sampai' => '2026-06-15'], 'Asia/Jakarta'),
            $pengguna,
        );

        $this->assertSame('2026-01-01 s.d. 2026-01-04', $baris[1][2]);
        $this->assertSame(KatalogKpi::ambil('keluhan.masuk')->nama, $baris[1][0]);
    }

    public function test_tanggal_masukan_dibaca_ketat(): void
    {
        $this->assertSame('2026-02-28', FilterMetrik::tanggalMasukan('2026-02-28T10:00:00', 'Asia/Jakarta')?->toDateString());
        $this->assertNull(FilterMetrik::tanggalMasukan('2026-02-29', 'Asia/Jakarta'));
        $this->assertNull(FilterMetrik::tanggalMasukan('26-02-01', 'Asia/Jakarta'));
        $this->assertNull(FilterMetrik::tanggalMasukan(['2026-02-01'], 'Asia/Jakarta'));
        $this->assertFalse(FilterMetrik::tanggalTidakSah(null, 'Asia/Jakarta'));
        $this->assertFalse(FilterMetrik::tanggalTidakSah('', 'Asia/Jakarta'));
        $this->assertTrue(FilterMetrik::tanggalTidakSah('kemarin', 'Asia/Jakarta'));
    }

    /** Beberapa keluhan per hari (siang WIB) sepanjang rentang, lewat query builder supaya cepat. */
    private function semaiKeluhanHarian(string $dari, string $sampai, int $perHari): void
    {
        $lokasi = Lokasi::create(['OrganisasiId' => $this->organisasi->Id, 'Kode' => 'LOK-'.uniqid(), 'Nama' => 'Poli', 'Status' => 'Aktif']);
        $kategori = KategoriKeluhan::create(['OrganisasiId' => $this->organisasi->Id, 'Kode' => 'KAT-'.uniqid(), 'Nama' => 'Kategori']);

        $baris = [];
        for ($hari = CarbonImmutable::parse($dari, 'UTC'); $hari->lessThanOrEqualTo(CarbonImmutable::parse($sampai, 'UTC')); $hari = $hari->addDay()) {
            for ($ke = 0; $ke < $perHari; $ke++) {
                $baris[] = [
                    'Id' => strtolower((string) Str::ulid()),
                    'OrganisasiId' => $this->organisasi->Id,
                    'Nomor' => 'KLH-'.Str::random(12),
                    'KategoriKeluhanId' => $kategori->Id,
                    'LokasiId' => $lokasi->Id,
                    'Judul' => 'Keluhan harian',
                    'Deskripsi' => 'Semai deret.',
                    'Prioritas' => 'Normal',
                    'Status' => 'Baru',
                    'NamaPelaporEksternal' => 'Perawat',
                    'DilaporkanPada' => $hari->setTime(5, $ke)->format('Y-m-d H:i:s'),
                    'DibuatPada' => '2026-06-01 00:00:00',
                    'DiperbaruiPada' => '2026-06-01 00:00:00',
                ];
            }
        }

        foreach (array_chunk($baris, 500) as $potongan) {
            DB::table('Keluhan')->insert($potongan);
        }
    }
}
