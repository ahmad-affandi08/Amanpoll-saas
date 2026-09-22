<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Pemasaran;

use App\Domain\Pemasaran\Application\Services\PenghitungKpiPemasaran;
use App\Domain\Pemasaran\Application\Services\PenyusunFunnelGrowth;
use App\Domain\Pemasaran\Domain\Enums\TahapFunnelGrowth;
use App\Domain\Pemasaran\Domain\KatalogKpiPemasaran;
use App\Domain\Pemasaran\Domain\KatalogPeristiwaPemasaran;
use App\Domain\Pemasaran\Domain\ValueObjects\DefinisiKpiPemasaran;
use App\Domain\Pemasaran\Domain\ValueObjects\FilterGrowth;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Prospek;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\SesiPengunjung;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Trial;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Angka dashboard growth harus cocok dengan tabel transaksinya (Gate 37). */
final class AuditPemasaranTest extends KasusGrowth
{
    /** Inti Gate 37: tiap tahap funnel dihitung ulang langsung dari tabelnya dan harus sama. */
    public function test_funnel_konsisten_dengan_data_transaksi(): void
    {
        $this->semaiPerjalanan();

        $funnel = app(PenyusunFunnelGrowth::class)->hitung($this->filter());

        $this->assertSame(
            SesiPengunjung::query()->distinct()->count('PengenalPengunjung'),
            $funnel[TahapFunnelGrowth::Visitor->value],
        );
        $this->assertSame(
            Prospek::query()->count(),
            $funnel[TahapFunnelGrowth::Lead->value],
        );
        $this->assertSame(
            DB::table('EventPemasaran')
                ->where('Jenis', KatalogPeristiwaPemasaran::DEMO_DIMULAI)
                ->distinct()
                ->count('PengenalPengunjung'),
            $funnel[TahapFunnelGrowth::Demo->value],
        );
        $this->assertSame(
            Trial::query()->count(),
            $funnel[TahapFunnelGrowth::Trial->value],
        );
        $this->assertSame(
            Trial::query()->whereNotNull('TeraktivasiPada')->count(),
            $funnel[TahapFunnelGrowth::Activated->value],
        );
        $this->assertSame(
            Trial::query()->whereNotNull('KonversiPada')->count(),
            $funnel[TahapFunnelGrowth::Paid->value],
        );
    }

    /** Tahap yang diam-diam menyalin angka tahap lain hanya ketahuan bila angkanya memang berbeda. */
    public function test_tiap_tahap_menghasilkan_angka_yang_berbeda(): void
    {
        $this->semaiPerjalanan();

        $funnel = app(PenyusunFunnelGrowth::class)->hitung($this->filter());

        $this->assertSame(3, $funnel[TahapFunnelGrowth::Visitor->value]);
        $this->assertSame(2, $funnel[TahapFunnelGrowth::Lead->value]);
        $this->assertSame(1, $funnel[TahapFunnelGrowth::Demo->value]);
        $this->assertSame(2, $funnel[TahapFunnelGrowth::Trial->value]);
        $this->assertSame(1, $funnel[TahapFunnelGrowth::Activated->value]);
        $this->assertSame(1, $funnel[TahapFunnelGrowth::Qualified->value]);
        $this->assertSame(1, $funnel[TahapFunnelGrowth::Paid->value]);
    }

    public function test_funnel_menyempit_dari_tahap_ke_tahap(): void
    {
        $this->semaiPerjalanan();

        $funnel = app(PenyusunFunnelGrowth::class)->hitung($this->filter());

        $this->assertGreaterThanOrEqual(
            $funnel[TahapFunnelGrowth::Lead->value],
            $funnel[TahapFunnelGrowth::Visitor->value],
        );
        $this->assertGreaterThanOrEqual(
            $funnel[TahapFunnelGrowth::Activated->value],
            $funnel[TahapFunnelGrowth::Trial->value],
        );
        $this->assertGreaterThanOrEqual(
            $funnel[TahapFunnelGrowth::Paid->value],
            $funnel[TahapFunnelGrowth::Activated->value],
        );
    }

    /** Rentang tanggal benar-benar memotong: yang di luar jendela tidak ikut terhitung. */
    public function test_rentang_tanggal_memotong_angkanya(): void
    {
        $this->semaiPerjalanan();
        $this->buatPengunjung('lama', CarbonImmutable::now()->subDays(120));

        $semua = app(PenyusunFunnelGrowth::class)->hitung(
            new FilterGrowth(CarbonImmutable::now()->subDays(365), CarbonImmutable::now()),
        );
        $sempit = app(PenyusunFunnelGrowth::class)->hitung($this->filter());

        $this->assertSame(
            $sempit[TahapFunnelGrowth::Visitor->value] + 1,
            $semua[TahapFunnelGrowth::Visitor->value],
        );
    }

    public function test_filter_channel_menyaring_seluruh_tahap(): void
    {
        $this->semaiPerjalanan();

        $iklan = app(PenyusunFunnelGrowth::class)->hitung($this->filter(channel: 'iklan'));
        $organik = app(PenyusunFunnelGrowth::class)->hitung($this->filter(channel: 'organik'));

        $this->assertSame(1, $iklan[TahapFunnelGrowth::Visitor->value]);
        $this->assertSame(1, $iklan[TahapFunnelGrowth::Paid->value]);
        $this->assertSame(2, $organik[TahapFunnelGrowth::Visitor->value]);
        $this->assertSame(0, $organik[TahapFunnelGrowth::Paid->value]);
    }

    public function test_filter_perangkat_dan_landing_menyaring_pengunjungnya(): void
    {
        $this->semaiPerjalanan();

        $ponsel = app(PenyusunFunnelGrowth::class)->hitung($this->filter(perangkat: 'Ponsel'));
        $harga = app(PenyusunFunnelGrowth::class)->hitung($this->filter(landing: '/harga'));

        $this->assertSame(1, $ponsel[TahapFunnelGrowth::Visitor->value]);
        $this->assertSame(1, $harga[TahapFunnelGrowth::Visitor->value]);
    }

    /** Rasio dibaca dari funnel yang sama, sehingga tidak pernah bertentangan dengan angkanya. */
    public function test_rasio_kpi_sejalan_dengan_funnelnya(): void
    {
        $this->semaiPerjalanan();

        $filter = $this->filter();
        $funnel = app(PenyusunFunnelGrowth::class)->hitung($filter);
        $kpi = app(PenghitungKpiPemasaran::class)->hitung($filter, $funnel);

        $harapan = round(
            $funnel[TahapFunnelGrowth::Lead->value] / $funnel[TahapFunnelGrowth::Visitor->value] * 100,
            1,
        );

        $this->assertSame($harapan, $kpi[KatalogKpiPemasaran::VISITOR_KE_LEAD]);
        $this->assertSame(
            (float) $funnel[TahapFunnelGrowth::Trial->value],
            $kpi[KatalogKpiPemasaran::TRIAL_TERDAFTAR],
        );
    }

    public function test_rasio_tanpa_penyebut_menjadi_nol_bukan_galat(): void
    {
        $filter = $this->filter();

        $kpi = app(PenghitungKpiPemasaran::class)->hitung($filter);

        $this->assertSame(0.0, $kpi[KatalogKpiPemasaran::VISITOR_KE_LEAD]);
        $this->assertSame(0.0, $kpi[KatalogKpiPemasaran::AKTIVASI_KE_BAYAR]);
    }

    public function test_revenue_per_channel_menjumlah_pembayaran_yang_berhasil(): void
    {
        $this->semaiPerjalanan();

        $revenue = app(PenghitungKpiPemasaran::class)->revenuePerChannel($this->filter());

        $this->assertSame(['iklan' => 250000.0], $revenue);
    }

    /** Tiap KPI yang dinyatakan tersedia harus benar-benar punya angkanya. */
    public function test_seluruh_kpi_tersedia_menghasilkan_angka(): void
    {
        $this->semaiPerjalanan();

        $kpi = app(PenghitungKpiPemasaran::class)->hitung($this->filter());

        foreach (KatalogKpiPemasaran::kunciTersedia() as $kunci) {
            $this->assertArrayHasKey($kunci, $kpi, "KPI {$kunci} dinyatakan tersedia tetapi tidak dihitung.");
        }
    }

    /**
     * Sejak FASE 38.09 seluruh KPI punya sumbernya, jadi daftar yang belum tersedia kosong.
     *
     * Penjaga di bawahnya tetap dipasang: KPI yang kelak ditambahkan sebelum sumbernya ada
     * harus menyebut alasannya dan tidak boleh ikut dihitung, bukan diam-diam bernilai nol.
     */
    public function test_kpi_belum_tersedia_menyebut_alasannya(): void
    {
        $this->assertSame(
            array_keys(KatalogKpiPemasaran::semua()),
            KatalogKpiPemasaran::kunciTersedia(),
        );

        $belum = array_filter(
            KatalogKpiPemasaran::semua(),
            fn (DefinisiKpiPemasaran $satu): bool => ! $satu->tersedia(),
        );

        foreach ($belum as $satu) {
            $this->assertNotSame('', (string) $satu->belumTersedia, "KPI {$satu->kunci} tanpa alasan.");
            $this->assertArrayNotHasKey(
                $satu->kunci,
                app(PenghitungKpiPemasaran::class)->hitung($this->filter()),
                "KPI {$satu->kunci} belum tersedia tetapi tetap dihitung.",
            );
        }
    }

    /** Tiap tahap funnel menyebut tabel sumbernya, dan tabel itu harus benar-benar ada. */
    public function test_tiap_tahap_menunjuk_tabel_yang_ada(): void
    {
        foreach (TahapFunnelGrowth::cases() as $tahap) {
            $this->assertTrue(
                Schema::hasTable($tahap->sumber()),
                "Tahap {$tahap->value} menunjuk tabel {$tahap->sumber()} yang tidak ada.",
            );
        }
    }

    private function filter(
        ?string $channel = null,
        ?string $perangkat = null,
        ?string $landing = null,
    ): FilterGrowth {
        return new FilterGrowth(
            dari: CarbonImmutable::now()->subDays(30),
            sampai: CarbonImmutable::now()->addDay(),
            channel: $channel,
            perangkat: $perangkat,
            landing: $landing,
        );
    }
}
