<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Pemasaran;

use App\Core\Host\PetaHost;
use App\Domain\Pemasaran\Application\Services\LayananKonfigurasiPemasaran;
use App\Domain\Pemasaran\Application\Services\PembacaSentuhan;
use App\Domain\Pemasaran\Application\Services\PenghitungRevenueAttribution;
use App\Domain\Pemasaran\Domain\Enums\ModelAttribution;
use App\Domain\Pemasaran\Domain\KatalogKonfigurasiPemasaran;
use App\Domain\Pemasaran\Domain\ValueObjects\FilterGrowth;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\AttributionPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\SesiPengunjung;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\UtmPemasaran;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

/** Revenue per channel dapat dibaca menurut model yang dipilih (Gate 38.10). */
final class ModelAttributionTest extends KasusGrowth
{
    private function revenue(): PenghitungRevenueAttribution
    {
        return app(PenghitungRevenueAttribution::class);
    }

    private function filter(): FilterGrowth
    {
        return new FilterGrowth(
            CarbonImmutable::now()->subDays(30)->startOfDay(),
            CarbonImmutable::now()->endOfDay(),
        );
    }

    private function pilihModel(ModelAttribution $model): void
    {
        app(LayananKonfigurasiPemasaran::class)
            ->simpan(KatalogKonfigurasiPemasaran::ATTRIBUTION_MODEL, $model->value);
    }

    /**
     * Tiga sentuhan, seluruhnya sebelum waktu pembayaran. Pengunjungnya dibuat
     * di sini, bukan lewat `buatPengunjung`, supaya tidak ada kunjungan tanpa
     * UTM yang ikut terhitung sebagai sentuhan keempat.
     */
    private function semaiTigaSentuhan(): string
    {
        $pengenal = (string) Str::ulid();

        foreach ([['google', 20], ['linkedin', 10], ['newsletter', 1]] as [$sumber, $hariLalu]) {
            $this->sentuh($pengenal, $sumber, $this->hariPerjalanan()->subDays($hariLalu));
        }

        return $pengenal;
    }

    private function sentuh(string $pengenal, string $sumber, CarbonImmutable $pada): void
    {
        $sesi = SesiPengunjung::create([
            'PengenalPengunjung' => $pengenal,
            'Perangkat' => 'Desktop',
            'LandingUrl' => '/',
            'Host' => 'publik.test',
            'DimulaiPada' => $pada,
            'TerakhirAktifPada' => $pada,
        ]);

        UtmPemasaran::create(['SesiPengunjungId' => $sesi->Id, 'Source' => $sumber, 'Medium' => 'cpc']);
    }

    private function semaiPembayaranTigaChannel(float $jumlah = 300_000): string
    {
        $pengenal = $this->semaiTigaSentuhan();

        $organisasi = $this->buatOrganisasi();
        $this->buatTrial($organisasi, null, $pengenal, teraktivasi: true, konversi: true);
        $this->bayar($organisasi, $jumlah);

        return $pengenal;
    }

    /** @return array<string, float> */
    private function perjalananTigaChannel(ModelAttribution $model): array
    {
        $this->semaiPembayaranTigaChannel();

        return $this->revenue()->perChannel($this->filter(), $model);
    }

    public function test_model_bawaan_adalah_sentuhan_pertama(): void
    {
        $this->assertSame(ModelAttribution::Pertama, $this->revenue()->modelAktif());
    }

    public function test_model_dapat_dipilih_lewat_setelan(): void
    {
        $this->pilihModel(ModelAttribution::Linear);

        $this->assertSame(ModelAttribution::Linear, $this->revenue()->modelAktif());
    }

    /** Nilai setelan yang tidak dikenal tidak boleh diam-diam mengubah angka dashboard. */
    public function test_setelan_model_asing_ditolak_konsol(): void
    {
        $this->actingAs($this->buatAdmin(superAdmin: true), 'platform')
            ->post(route('pemasaran.pengaturan.konfigurasi'), [
                'Kunci' => KatalogKonfigurasiPemasaran::ATTRIBUTION_MODEL,
                'Nilai' => 'ModelKarangan',
            ])
            ->assertSessionHasErrors('Nilai');

        $this->assertSame(ModelAttribution::Pertama, $this->revenue()->modelAktif());
    }

    public function test_konsol_dapat_memilih_model_yang_dikenal(): void
    {
        $this->actingAs($this->buatAdmin(superAdmin: true), 'platform')
            ->post(route('pemasaran.pengaturan.konfigurasi'), [
                'Kunci' => KatalogKonfigurasiPemasaran::ATTRIBUTION_MODEL,
                'Nilai' => ModelAttribution::PositionBased->value,
            ])
            ->assertRedirect();

        $this->assertSame(ModelAttribution::PositionBased, $this->revenue()->modelAktif());
    }

    /** Inti Gate 38.10 pada sisi revenue: seluruh uang terbagi, tidak lebih dan tidak kurang. */
    public function test_setiap_model_membagi_seluruh_revenue_tanpa_sisa(): void
    {
        $this->semaiPembayaranTigaChannel();

        foreach (ModelAttribution::cases() as $model) {
            $perChannel = $this->revenue()->perChannel($this->filter(), $model);

            $this->assertEqualsWithDelta(
                300_000.0,
                array_sum($perChannel),
                0.02,
                "Model {$model->value} tidak membagi seluruh revenue.",
            );
        }
    }

    public function test_model_pertama_memberi_seluruh_revenue_ke_channel_pertama(): void
    {
        $hasil = $this->perjalananTigaChannel(ModelAttribution::Pertama);

        $this->assertSame(300_000.0, $hasil['google'] ?? 0.0);
        $this->assertArrayNotHasKey('newsletter', $hasil);
    }

    public function test_model_terakhir_memberi_seluruh_revenue_ke_channel_terakhir(): void
    {
        $hasil = $this->perjalananTigaChannel(ModelAttribution::Terakhir);

        $this->assertSame(300_000.0, $hasil['newsletter'] ?? 0.0);
        $this->assertArrayNotHasKey('google', $hasil);
    }

    public function test_model_linear_membagi_revenue_rata(): void
    {
        $hasil = $this->perjalananTigaChannel(ModelAttribution::Linear);

        // Pembagian bulat menyisakan satu satuan dari sejuta, jadi tiap channel boleh meleset 0,3 rupiah.
        foreach (['google', 'linkedin', 'newsletter'] as $channel) {
            $this->assertEqualsWithDelta(100_000.0, $hasil[$channel] ?? 0.0, 0.5);
        }

        $this->assertSame(300_000.0, round(array_sum($hasil), 2));
    }

    public function test_model_berbasis_posisi_memberi_ujung_lebih_besar(): void
    {
        $hasil = $this->perjalananTigaChannel(ModelAttribution::PositionBased);

        $this->assertEqualsWithDelta(120_000.0, $hasil['google'] ?? 0.0, 0.5);
        $this->assertEqualsWithDelta(60_000.0, $hasil['linkedin'] ?? 0.0, 0.5);
        $this->assertEqualsWithDelta(120_000.0, $hasil['newsletter'] ?? 0.0, 0.5);
    }

    public function test_model_peluruhan_memberi_yang_terdekat_paling_besar(): void
    {
        $hasil = $this->perjalananTigaChannel(ModelAttribution::TimeDecay);

        $this->assertGreaterThan($hasil['linkedin'], $hasil['newsletter']);
        $this->assertGreaterThan($hasil['google'], $hasil['linkedin']);
    }

    /** Model berbeda menghasilkan angka berbeda; tanpa ini pilihan setelan hanya hiasan. */
    public function test_model_yang_berbeda_menghasilkan_angka_yang_berbeda(): void
    {
        $this->semaiPembayaranTigaChannel();

        $pertama = $this->revenue()->perChannel($this->filter(), ModelAttribution::Pertama);
        $linear = $this->revenue()->perChannel($this->filter(), ModelAttribution::Linear);

        $this->assertNotEquals($pertama, $linear);
    }

    /** Dashboard membaca model dari setelan, bukan dari parameter layar. */
    public function test_dashboard_memakai_model_dari_setelan(): void
    {
        $this->semaiPembayaranTigaChannel();
        $this->pilihModel(ModelAttribution::Linear);

        $props = $this->actingAs($this->buatAdmin(superAdmin: true), 'platform')
            ->get(route('pemasaran.growth.index'))
            ->assertOk()
            ->viewData('page')['props'];

        $this->assertSame(ModelAttribution::Linear->value, $props['attribution']['Model']);
        $this->assertEqualsWithDelta(100_000.0, $props['revenuePerChannel']['google'], 0.5);
    }

    /** Pembayaran yang pengunjungnya tidak punya sentuhan tetap dilaporkan, bukan hilang. */
    public function test_pembayaran_tanpa_sentuhan_masuk_keranjang_sendiri(): void
    {
        $organisasi = $this->buatOrganisasi();
        $this->buatTrial($organisasi, null, null);
        $this->bayar($organisasi, 500_000);

        $hasil = $this->revenue()->perChannel($this->filter(), ModelAttribution::Linear);

        $this->assertSame(500_000.0, $hasil[PenghitungRevenueAttribution::TANPA_SENTUHAN] ?? 0.0);
    }

    /** Kunjungan berurutan dari sumber yang sama adalah satu sentuhan, bukan banyak. */
    public function test_kunjungan_berulang_dari_sumber_sama_tidak_menggandakan_bobotnya(): void
    {
        $pengenal = (string) Str::ulid();

        foreach ([20, 19, 18] as $hariLalu) {
            $this->sentuh($pengenal, 'google', CarbonImmutable::now()->subDays($hariLalu));
        }

        $this->sentuh($pengenal, 'linkedin', CarbonImmutable::now()->subDay());

        $sentuhan = app(PembacaSentuhan::class)->untuk($pengenal, CarbonImmutable::now());

        $this->assertCount(2, $sentuhan);
        $this->assertSame('google', $sentuhan[0]->sumber);
        $this->assertSame('linkedin', $sentuhan[1]->sumber);
    }

    /** Sentuhan yang lebih tua dari jendela attribution tidak lagi diperhitungkan. */
    public function test_sentuhan_di_luar_jendela_tidak_diperhitungkan(): void
    {
        $pengenal = (string) Str::ulid();
        $this->sentuh($pengenal, 'google', CarbonImmutable::now()->subDays(20));
        $this->sentuh($pengenal, 'newsletter', CarbonImmutable::now()->subDay());

        $this->assertCount(2, app(PembacaSentuhan::class)->untuk($pengenal, CarbonImmutable::now()));

        app(LayananKonfigurasiPemasaran::class)
            ->simpan(KatalogKonfigurasiPemasaran::ATTRIBUTION_JENDELA_HARI, 5);

        $sentuhan = app(PembacaSentuhan::class)->untuk($pengenal, CarbonImmutable::now());

        $this->assertCount(1, $sentuhan);
        $this->assertSame('newsletter', $sentuhan[0]->sumber);
    }

    /** Sentuhan yang dibaca ulang harus cocok dengan first touch yang tercatat saat kunjungan. */
    public function test_sentuhan_pertama_yang_dibaca_cocok_dengan_yang_tercatat(): void
    {
        $pertama = $this->get($this->urlPublik('/?utm_source=google&utm_medium=cpc'));
        $pengenal = (string) $pertama->getCookie('amanpoll_pengunjung')?->getValue();

        $this->withCookie('amanpoll_pengunjung', $pengenal)
            ->get($this->urlPublik('/?utm_source=linkedin&utm_medium=social'))
            ->assertOk();

        $sentuhan = app(PembacaSentuhan::class)->untuk($pengenal, CarbonImmutable::now());
        $tercatat = AttributionPemasaran::query()
            ->where('PengenalPengunjung', $pengenal)
            ->firstOrFail();

        $this->assertSame($tercatat->SumberPertama, $sentuhan[0]->sumber);
        $this->assertSame($tercatat->MediumPertama, $sentuhan[0]->medium);
        $this->assertSame($tercatat->SumberTerakhir, $sentuhan[array_key_last($sentuhan)]->sumber);
    }

    /** Kunjungan setelah orang membayar bukan sentuhan yang mengantarnya membayar. */
    public function test_sentuhan_setelah_konversi_tidak_diperhitungkan(): void
    {
        $pengenal = (string) Str::ulid();
        $this->sentuh($pengenal, 'google', $this->hariPerjalanan()->subDay());

        $organisasi = $this->buatOrganisasi();
        $this->buatTrial($organisasi, null, $pengenal, teraktivasi: true, konversi: true);
        $this->bayar($organisasi, 300_000);

        // Dikunjungi lagi sehari setelah membayar.
        $this->sentuh($pengenal, 'newsletter', $this->hariPerjalanan()->addDay());

        $hasil = $this->revenue()->perChannel($this->filter(), ModelAttribution::Linear);

        $this->assertSame(300_000.0, $hasil['google'] ?? 0.0);
        $this->assertArrayNotHasKey('newsletter', $hasil);
    }

    /** Sumber yang sama dengan kampanye berbeda adalah dua sentuhan, bukan satu. */
    public function test_kampanye_berbeda_dari_sumber_sama_tetap_dua_sentuhan(): void
    {
        $pengenal = (string) Str::ulid();

        foreach ([['musim-semi', 20], ['musim-gugur', 10]] as [$kampanye, $hariLalu]) {
            $waktu = CarbonImmutable::now()->subDays($hariLalu);
            $sesi = SesiPengunjung::create([
                'PengenalPengunjung' => $pengenal,
                'Perangkat' => 'Desktop',
                'LandingUrl' => '/',
                'Host' => 'publik.test',
                'DimulaiPada' => $waktu,
                'TerakhirAktifPada' => $waktu,
            ]);
            UtmPemasaran::create([
                'SesiPengunjungId' => $sesi->Id,
                'Source' => 'google',
                'Medium' => 'cpc',
                'Campaign' => $kampanye,
            ]);
        }

        $sentuhan = app(PembacaSentuhan::class)->untuk($pengenal, CarbonImmutable::now());

        $this->assertCount(2, $sentuhan);
        $this->assertSame('musim-semi', $sentuhan[0]->kampanye);
        $this->assertSame('musim-gugur', $sentuhan[1]->kampanye);
    }

    /** Medium ikut membedakan sentuhan, jadi ia harus benar-benar terbaca dari UTM. */
    public function test_medium_dibaca_dari_utm(): void
    {
        $pengenal = (string) Str::ulid();
        $waktu = CarbonImmutable::now()->subDay();
        $sesi = SesiPengunjung::create([
            'PengenalPengunjung' => $pengenal,
            'Perangkat' => 'Desktop',
            'LandingUrl' => '/',
            'Host' => 'publik.test',
            'DimulaiPada' => $waktu,
            'TerakhirAktifPada' => $waktu,
        ]);
        UtmPemasaran::create(['SesiPengunjungId' => $sesi->Id, 'Source' => 'google', 'Medium' => 'display']);

        $sentuhan = app(PembacaSentuhan::class)->untuk($pengenal, CarbonImmutable::now());

        $this->assertSame('display', $sentuhan[0]->medium);
    }

    /**
     * Pengunjungnya ada, tetapi seluruh sentuhannya sudah di luar jendela.
     * Uangnya tetap harus dilaporkan, bukan lenyap dari tabel revenue.
     */
    public function test_pembayaran_yang_seluruh_sentuhannya_kedaluwarsa_tetap_dilaporkan(): void
    {
        $pengenal = (string) Str::ulid();
        $this->sentuh($pengenal, 'google', $this->hariPerjalanan()->subDays(20));

        $organisasi = $this->buatOrganisasi();
        $this->buatTrial($organisasi, null, $pengenal, teraktivasi: true, konversi: true);
        $this->bayar($organisasi, 300_000);

        app(LayananKonfigurasiPemasaran::class)
            ->simpan(KatalogKonfigurasiPemasaran::ATTRIBUTION_JENDELA_HARI, 5);

        $hasil = $this->revenue()->perChannel($this->filter(), ModelAttribution::Linear);

        $this->assertSame(300_000.0, $hasil[PenghitungRevenueAttribution::TANPA_SENTUHAN] ?? 0.0);
        $this->assertArrayNotHasKey('google', $hasil);
    }

    /**
     * Riwayat kunjungan boleh saja sudah dipangkas sementara barisnya di
     * AttributionPemasaran masih ada; angkanya tidak boleh hilang karenanya.
     */
    public function test_pengunjung_tanpa_riwayat_sesi_memakai_attribution_yang_tercatat(): void
    {
        $pengenal = $this->buatPengunjung('iklan');
        SesiPengunjung::query()->where('PengenalPengunjung', $pengenal)->delete();

        $sentuhan = app(PembacaSentuhan::class)->untuk($pengenal, CarbonImmutable::now());

        $this->assertCount(1, $sentuhan);
        $this->assertSame('iklan', $sentuhan[0]->sumber);
    }

    /** Dua sentuhan tercatat yang berbeda tetap dua, bukan dilebur jadi satu. */
    public function test_cadangan_memakai_kedua_sentuhan_yang_tercatat(): void
    {
        $pengenal = $this->buatPengunjung('iklan');
        SesiPengunjung::query()->where('PengenalPengunjung', $pengenal)->delete();
        AttributionPemasaran::query()
            ->where('PengenalPengunjung', $pengenal)
            ->update(['SumberTerakhir' => 'newsletter']);

        $sentuhan = app(PembacaSentuhan::class)->untuk($pengenal, CarbonImmutable::now());

        $this->assertCount(2, $sentuhan);
        $this->assertSame('iklan', $sentuhan[0]->sumber);
        $this->assertSame('newsletter', $sentuhan[1]->sumber);
    }

    /** Sesi yang ada tetapi seluruhnya di luar jendela bukan riwayat yang hilang. */
    public function test_sesi_di_luar_jendela_tidak_jatuh_ke_cadangan(): void
    {
        $pengenal = (string) Str::ulid();
        $this->sentuh($pengenal, 'google', CarbonImmutable::now()->subDays(20));
        AttributionPemasaran::create([
            'PengenalPengunjung' => $pengenal,
            'SumberPertama' => 'iklan',
            'MediumPertama' => 'cpc',
            'SentuhanPertamaPada' => CarbonImmutable::now()->subDays(20),
            'SumberTerakhir' => 'iklan',
            'MediumTerakhir' => 'cpc',
            'SentuhanTerakhirPada' => CarbonImmutable::now()->subDays(20),
        ]);

        app(LayananKonfigurasiPemasaran::class)
            ->simpan(KatalogKonfigurasiPemasaran::ATTRIBUTION_JENDELA_HARI, 5);

        $this->assertSame([], app(PembacaSentuhan::class)->untuk($pengenal, CarbonImmutable::now()));
    }

    private function urlPublik(string $path = '/'): string
    {
        return 'http://'.(string) app(PetaHost::class)->publik().'/'.ltrim($path, '/');
    }
}
