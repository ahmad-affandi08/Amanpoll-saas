<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Pemasaran;

use App\Domain\Pemasaran\Application\Services\LayananKonfigurasiPemasaran;
use App\Domain\Pemasaran\Application\Services\PemeriksaAlertPemasaran;
use App\Domain\Pemasaran\Domain\KatalogAlertPemasaran;
use App\Domain\Pemasaran\Domain\KatalogIzinPemasaran;
use App\Domain\Pemasaran\Domain\KatalogKonfigurasiPemasaran;
use App\Domain\Pemasaran\Domain\ValueObjects\FilterGrowth;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\AlertPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Kampanye;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\MetrikKampanye;
use App\Domain\Pemasaran\Jobs\HitungMetrikKampanye;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/** Halaman dashboard growth, metrik terhitung, dan alert platform (MARKETING.md 5). */
final class DashboardGrowthTest extends KasusGrowth
{
    private const AKAR = '/admin-platform/pemasaran/growth';

    public function test_membuka_dashboard_menuntut_izin_analytics(): void
    {
        $this->aktingSebagai([])->get(self::AKAR)->assertForbidden();
        $this->aktingSebagai([KatalogIzinPemasaran::ANALYTICS_LIHAT])->get(self::AKAR)->assertOk();
    }

    public function test_dashboard_menampilkan_funnel_dan_kpinya(): void
    {
        $this->semaiPerjalanan();

        $this->aktingSebagai([KatalogIzinPemasaran::ANALYTICS_LIHAT])
            ->get(self::AKAR.'?dari='.CarbonImmutable::now()->subDays(30)->toDateString())
            ->assertOk()
            ->assertInertia(fn ($halaman) => $halaman
                ->component('Pemasaran/Growth/Dashboard')
                ->has('funnel', 7)
                ->has('kpi')
                ->has('filter'));
    }

    /** Jumlah kueri halaman tidak boleh tumbuh mengikuti jumlah kampanye; itulah alasan metrik dihitung di muka. */
    public function test_menambah_kampanye_tidak_menambah_kueri_halaman(): void
    {
        $this->semaiPerjalanan();
        $this->aktingSebagai([KatalogIzinPemasaran::ANALYTICS_LIHAT]);

        $satu = $this->hitungKueri();

        for ($i = 0; $i < 5; $i++) {
            $this->buatMetrikKampanye('kampanye-'.$i);
        }

        $banyak = $this->hitungKueri();

        $this->assertSame($satu, $banyak, "Kueri bertambah dari {$satu} menjadi {$banyak}.");
    }

    public function test_job_metrik_menulis_corong_per_kampanye(): void
    {
        $this->semaiPerjalanan();

        (new HitungMetrikKampanye($this->hariPerjalanan()->toDateString()))->handle();

        $baris = MetrikKampanye::query()->where('Channel', 'iklan')->firstOrFail();
        $this->assertSame(1, $baris->Trial);
        $this->assertSame(1, $baris->Teraktivasi);
        $this->assertSame(1, $baris->Bayar);
    }

    public function test_job_metrik_dijalankan_dua_kali_tidak_menggandakan_barisnya(): void
    {
        $this->semaiPerjalanan();
        $hari = $this->hariPerjalanan()->toDateString();

        (new HitungMetrikKampanye($hari))->handle();
        $pertama = MetrikKampanye::query()->count();

        (new HitungMetrikKampanye($hari))->handle();

        $this->assertSame($pertama, MetrikKampanye::query()->count());
    }

    public function test_alert_konversi_trial_menyala_di_bawah_ambangnya(): void
    {
        app(LayananKonfigurasiPemasaran::class)
            ->simpan(KatalogKonfigurasiPemasaran::ALERT_TRIAL_KONVERSI_MIN, 90);

        $this->buatEnamTrial();

        app(PemeriksaAlertPemasaran::class)->periksa();

        $this->assertSame(1, AlertPemasaran::query()
            ->where('Kode', KatalogAlertPemasaran::TRIAL_KONVERSI_TURUN)
            ->count());
    }

    /** Pemeriksa yang berjalan tiap jam tidak boleh menimbun alert yang sama. */
    public function test_memeriksa_dua_kali_sehari_tidak_menggandakan_alertnya(): void
    {
        app(LayananKonfigurasiPemasaran::class)
            ->simpan(KatalogKonfigurasiPemasaran::ALERT_TRIAL_KONVERSI_MIN, 90);

        $this->buatEnamTrial();

        $pertama = app(PemeriksaAlertPemasaran::class)->periksa();
        $kedua = app(PemeriksaAlertPemasaran::class)->periksa();

        // Pemeriksaan kedua tidak boleh melaporkan alert baru, bukan sekadar tidak menulis baris baru.
        $this->assertCount(1, $pertama);
        $this->assertSame([], $kedua);
        $this->assertSame(1, AlertPemasaran::query()
            ->where('Kode', KatalogAlertPemasaran::TRIAL_KONVERSI_TURUN)
            ->count());
    }

    public function test_alert_tidak_menyala_saat_semuanya_sehat(): void
    {
        $this->semaiPerjalanan();

        app(PemeriksaAlertPemasaran::class)->periksa();

        $this->assertSame(0, AlertPemasaran::query()
            ->where('Kode', KatalogAlertPemasaran::TRIAL_KONVERSI_TURUN)
            ->count());
    }

    /** Sejak FASE 38.09 seluruh alert punya sumbernya; tidak ada lagi yang menunggu fase berikutnya. */
    public function test_seluruh_alert_katalog_sudah_punya_sumbernya(): void
    {
        foreach (KatalogAlertPemasaran::kode() as $kode) {
            $this->assertTrue(
                KatalogAlertPemasaran::tersedia($kode),
                "Alert {$kode} masih dinyatakan belum tersedia.",
            );
        }
    }

    /** Tanpa komisi yang menggantung, alert komisi partner tetap diam. */
    public function test_alert_komisi_partner_tidak_menyala_tanpa_komisi_tertunda(): void
    {
        app(PemeriksaAlertPemasaran::class)->periksa();

        $this->assertSame(0, AlertPemasaran::query()
            ->where('Kode', KatalogAlertPemasaran::KOMISI_PARTNER_TERTUNDA)
            ->count());
    }

    public function test_alert_dapat_ditandai_selesai(): void
    {
        $alert = app(PemeriksaAlertPemasaran::class)->catat(
            KatalogAlertPemasaran::REWARD_REFERRAL_GAGAL,
            'Satu imbalan gagal.',
            ['Jumlah' => 1],
            CarbonImmutable::now(),
        );
        $this->assertNotNull($alert);

        $this->aktingSebagai([KatalogIzinPemasaran::PEMASARAN_KELOLA])
            ->post(self::AKAR."/alert/{$alert->Id}/selesai")
            ->assertRedirect();

        $this->assertNotNull($alert->fresh()?->DiselesaikanPada);
    }

    /** Satu organisasi hanya boleh punya satu trial, jadi enam trial berarti enam organisasi. */
    private function buatEnamTrial(): void
    {
        for ($i = 0; $i < 6; $i++) {
            $this->buatTrial($this->buatOrganisasi(), null, $this->buatPengunjung('organik'));
        }
    }

    private function hitungKueri(): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->get(self::AKAR)->assertOk();

        $jumlah = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $jumlah;
    }

    private function buatMetrikKampanye(string $kode): void
    {
        $kampanye = Kampanye::create([
            'Kode' => $kode,
            'Nama' => 'Kampanye '.$kode,
            'Objective' => 'Lead',
            'Status' => 'Aktif',
        ]);

        MetrikKampanye::create([
            'KampanyeId' => $kampanye->Id,
            'Channel' => 'iklan',
            'Tanggal' => CarbonImmutable::now()->subDay()->toDateString(),
            'Visitor' => 10,
            'Lead' => 5,
            'Trial' => 2,
            'Teraktivasi' => 1,
            'Bayar' => 1,
            'Revenue' => 100_000,
            'DihitungPada' => CarbonImmutable::now(),
        ]);
    }

    /** @param list<string> $izin */
    private function aktingSebagai(array $izin): self
    {
        $this->actingAs($this->buatAdmin($izin), 'platform');

        return $this;
    }

    /**
     * Hari metrik pemasaran adalah hari di kalender vendor (WIB).
     *
     * Kunjungan pukul 01:00 WIB tanggal 22 tersimpan 18:00 UTC tanggal 21.
     * Ia milik metrik tanggal 22, dan "kemarin" bagi job yang berjalan
     * 01:30 WIB tanggal 23 adalah tanggal 22 -- bukan 21 menurut UTC.
     */
    public function test_hari_metrik_dan_rentang_dashboard_mengikuti_kalender_vendor(): void
    {
        $this->buatPengunjung('iklan', CarbonImmutable::parse('2026-09-21 18:00:00', 'UTC'));

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-09-22 18:30:00', 'UTC'));
        (new HitungMetrikKampanye)->handle();

        $baris = MetrikKampanye::query()->where('Channel', 'iklan')->sole();
        $this->assertSame('2026-09-22', $baris->Tanggal->toDateString());
        $this->assertSame(1, $baris->Visitor);

        $filter = FilterGrowth::dariKueri(['dari' => '2026-09-22', 'sampai' => '2026-09-22']);
        $this->assertSame('2026-09-21 17:00:00', $filter->dari->format('Y-m-d H:i:s'));
        $this->assertSame('2026-09-22 16:59:59', $filter->sampai->format('Y-m-d H:i:s'));
        $this->assertSame(['2026-09-22', '2026-09-22'], [$filter->keArray()['dari'], $filter->keArray()['sampai']]);
    }
}
