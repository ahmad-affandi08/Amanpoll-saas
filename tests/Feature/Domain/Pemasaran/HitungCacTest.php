<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Pemasaran;

use App\Domain\Pemasaran\Application\Services\PenghitungCacKampanye;
use App\Domain\Pemasaran\Application\Services\PenghitungKpiPemasaran;
use App\Domain\Pemasaran\Domain\Enums\ChannelKampanye;
use App\Domain\Pemasaran\Domain\KatalogKpiPemasaran;
use App\Domain\Pemasaran\Domain\ValueObjects\FilterGrowth;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Kampanye;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\KampanyeBiaya;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\KampanyeChannel;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\MetrikKampanye;
use App\Domain\Pemasaran\Jobs\HitungMetrikKampanye;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/** CAC harus sama dengan biaya dibagi pelanggan baru yang dihitung ulang dari tabelnya (Gate 38.08). */
final class HitungCacTest extends KasusGrowth
{
    /** Inti Gate 38.08: angka layar dibandingkan dengan hitungan ulang langsung dari dua tabel sumbernya. */
    public function test_cac_per_channel_sama_dengan_hitungan_ulang_dari_tabelnya(): void
    {
        $this->semaiKampanyeBerbayar();

        $perChannel = app(PenghitungCacKampanye::class)->perChannel($this->filter());
        $baris = $this->cariChannel($perChannel, ChannelKampanye::GoogleAds->value);

        $biaya = (float) DB::table('KampanyeBiaya')
            ->where('Channel', ChannelKampanye::GoogleAds->value)
            ->sum('Jumlah');
        $pelanggan = DB::table('Trial')
            ->join('AttributionPemasaran', 'AttributionPemasaran.PengenalPengunjung', '=', 'Trial.PengenalPengunjung')
            ->whereNotNull('Trial.KonversiPada')
            ->whereIn('AttributionPemasaran.KampanyeIdPertama', function ($kueri): void {
                $kueri->select('Id')->from('Kampanye')->where('Kode', 'iklan-google');
            })
            ->count();

        $this->assertSame(2, $pelanggan);
        $this->assertSame($biaya, $baris['Biaya']);
        $this->assertSame($pelanggan, $baris['Pelanggan']);
        $this->assertSame(round($biaya / $pelanggan, 2), $baris['Cac']);
    }

    /** Dua channel dengan biaya dan pelanggan berbeda; CAC yang menyalin angka tetangganya ketahuan di sini. */
    public function test_tiap_channel_menghasilkan_cac_yang_berbeda(): void
    {
        $this->semaiKampanyeBerbayar();

        $perChannel = app(PenghitungCacKampanye::class)->perChannel($this->filter());

        $google = $this->cariChannel($perChannel, ChannelKampanye::GoogleAds->value);
        $linkedin = $this->cariChannel($perChannel, ChannelKampanye::LinkedIn->value);

        $this->assertSame(300000.0, $google['Biaya']);
        $this->assertSame(2, $google['Pelanggan']);
        $this->assertSame(150000.0, $google['Cac']);

        $this->assertSame(400000.0, $linkedin['Biaya']);
        $this->assertSame(1, $linkedin['Pelanggan']);
        $this->assertSame(400000.0, $linkedin['Cac']);
    }

    /** Kampanye multi-channel tidak boleh dibagi rata; biayanya dilaporkan terpisah, bukan diselipkan. */
    public function test_kampanye_multi_channel_dilaporkan_terpisah(): void
    {
        $this->semaiKampanyeBerbayar();
        $campur = $this->buatKampanye('campur', [ChannelKampanye::MetaAds, ChannelKampanye::TikTok]);
        $this->biaya($campur, ChannelKampanye::MetaAds, 500_000);
        $this->pelangganBaru($campur, 'campur@pabrik.test');

        $perChannel = app(PenghitungCacKampanye::class)->perChannel($this->filter());
        $takTerpecah = app(PenghitungCacKampanye::class)->takTerpecah($this->filter());

        $this->assertNull($this->cariChannelAtauNull($perChannel, ChannelKampanye::MetaAds->value));
        $this->assertSame(500000.0, $takTerpecah['Biaya']);
        $this->assertSame(1, $takTerpecah['Pelanggan']);
        $this->assertSame(['campur'], $takTerpecah['Kampanye']);
    }

    /** Channel yang sudah dibelanjai tanpa pelanggan harus menyebut alasannya, bukan menampilkan CAC nol. */
    public function test_channel_tanpa_pelanggan_menyebut_alasannya(): void
    {
        $sepi = $this->buatKampanye('sepi', [ChannelKampanye::TikTok]);
        $this->biaya($sepi, ChannelKampanye::TikTok, 250_000);

        $baris = $this->cariChannel(
            app(PenghitungCacKampanye::class)->perChannel($this->filter()),
            ChannelKampanye::TikTok->value,
        );

        $this->assertNull($baris['Cac']);
        $this->assertSame(PenghitungCacKampanye::TANPA_PELANGGAN, $baris['Alasan']);
        $this->assertSame(250000.0, $baris['Biaya']);
    }

    /** Rentang tanggal benar-benar memotong biayanya: belanja di luar jendela tidak ikut menaikkan CAC. */
    public function test_rentang_tanggal_memotong_biayanya(): void
    {
        $this->semaiKampanyeBerbayar();
        $kampanye = Kampanye::query()->where('Kode', 'iklan-google')->firstOrFail();
        $this->biaya($kampanye, ChannelKampanye::GoogleAds, 900_000, $this->hariPerjalanan()->subDays(60));

        $sempit = $this->cariChannel(
            app(PenghitungCacKampanye::class)->perChannel($this->filter()),
            ChannelKampanye::GoogleAds->value,
        );
        $lebar = $this->cariChannel(
            app(PenghitungCacKampanye::class)->perChannel(new FilterGrowth(
                CarbonImmutable::now()->subDays(365),
                CarbonImmutable::now()->addDay(),
            )),
            ChannelKampanye::GoogleAds->value,
        );

        $this->assertSame(300000.0, $sempit['Biaya']);
        $this->assertSame(1200000.0, $lebar['Biaya']);
    }

    /** KPI cac_per_channel tidak lagi kosong, dan angkanya CAC gabungan seluruh kampanye. */
    public function test_kpi_cac_terisi_dan_sama_dengan_gabungannya(): void
    {
        $this->semaiKampanyeBerbayar();

        $kpi = app(PenghitungKpiPemasaran::class)->hitung($this->filter());

        $this->assertTrue(KatalogKpiPemasaran::ambil(KatalogKpiPemasaran::CAC_PER_CHANNEL)->tersedia());
        $this->assertArrayHasKey(KatalogKpiPemasaran::CAC_PER_CHANNEL, $kpi);
        // 700.000 rupiah belanja untuk tiga pelanggan baru.
        $this->assertSame(round(700000 / 3, 2), $kpi[KatalogKpiPemasaran::CAC_PER_CHANNEL]);
    }

    public function test_dashboard_growth_menampilkan_cac_per_channel(): void
    {
        $this->semaiKampanyeBerbayar();

        $props = $this->actingAs($this->buatAdmin(superAdmin: true), 'platform')
            ->get(route('pemasaran.growth.index', ['dari' => $this->dari(), 'sampai' => $this->sampai()]))
            ->viewData('page')['props'];

        $baris = $this->cariChannel($props['cacPerChannel'], ChannelKampanye::GoogleAds->value);

        $this->assertSame(150000.0, $baris['Cac']);
    }

    /** Biaya harian ikut dihitung pekerjaan metrik, sehingga tabel kampanye tidak perlu kueri kedua. */
    public function test_pekerjaan_metrik_menjumlahkan_biaya_harian(): void
    {
        $this->semaiKampanyeBerbayar();
        $hari = $this->hariPerjalanan();

        (new HitungMetrikKampanye($hari->toDateString()))->handle();
        (new HitungMetrikKampanye($hari->addDay()->toDateString()))->handle();

        $kampanye = Kampanye::query()->where('Kode', 'iklan-google')->firstOrFail();
        $total = MetrikKampanye::query()->where('KampanyeId', $kampanye->Id)->sum('Biaya');

        // Rp200.000 di hari pertama dan Rp100.000 di hari berikutnya, dijumlahkan dari dua baris harian.
        $this->assertSame(300000.0, round((float) $total, 2));
    }

    /** Biaya hari lain tidak boleh bocor ke baris hari ini. */
    public function test_biaya_hanya_masuk_ke_harinya_sendiri(): void
    {
        $this->semaiKampanyeBerbayar();
        $kampanye = Kampanye::query()->where('Kode', 'iklan-google')->firstOrFail();
        $this->biaya($kampanye, ChannelKampanye::GoogleAds, 111_000, $this->hariPerjalanan()->subDay());

        (new HitungMetrikKampanye($this->hariPerjalanan()->toDateString()))->handle();

        $total = MetrikKampanye::query()
            ->where('KampanyeId', $kampanye->Id)
            ->where('Tanggal', $this->hariPerjalanan()->toDateString())
            ->sum('Biaya');

        $this->assertSame(200000.0, round((float) $total, 2));
    }

    /** Google Ads Rp300.000 untuk dua pelanggan, LinkedIn Rp400.000 untuk satu; keduanya berchannel tunggal. */
    private function semaiKampanyeBerbayar(): void
    {
        $google = $this->buatKampanye('iklan-google', [ChannelKampanye::GoogleAds]);
        $linkedin = $this->buatKampanye('iklan-linkedin', [ChannelKampanye::LinkedIn]);

        $this->biaya($google, ChannelKampanye::GoogleAds, 200_000);
        $this->biaya($google, ChannelKampanye::GoogleAds, 100_000, $this->hariPerjalanan()->addDay());
        $this->biaya($linkedin, ChannelKampanye::LinkedIn, 400_000);

        $this->pelangganBaru($google, 'google-satu@pabrik.test');
        $this->pelangganBaru($google, 'google-dua@pabrik.test');
        $this->pelangganBaru($linkedin, 'linkedin-satu@pabrik.test');
    }

    /** @param list<ChannelKampanye> $channel */
    private function buatKampanye(string $kode, array $channel): Kampanye
    {
        $kampanye = Kampanye::create([
            'Kode' => $kode,
            'Nama' => 'Kampanye '.$kode,
            'Objective' => 'Lead',
            'Status' => 'Aktif',
        ]);

        foreach ($channel as $satu) {
            KampanyeChannel::create(['KampanyeId' => $kampanye->Id, 'Channel' => $satu->value]);
        }

        return $kampanye;
    }

    private function biaya(
        Kampanye $kampanye,
        ChannelKampanye $channel,
        float $jumlah,
        ?CarbonImmutable $pada = null,
    ): void {
        KampanyeBiaya::create([
            'KampanyeId' => $kampanye->Id,
            'Channel' => $channel->value,
            'Tanggal' => ($pada ?? $this->hariPerjalanan())->toDateString(),
            'Jumlah' => $jumlah,
        ]);
    }

    private function pelangganBaru(Kampanye $kampanye, string $email): void
    {
        $pengenal = $this->buatPengunjung('iklan', kampanyeId: $kampanye->Id);
        $prospek = $this->buatProspekUntuk($pengenal, $email, skor: 50);
        $organisasi = $this->buatOrganisasi();

        $this->buatTrial($organisasi, $prospek, $pengenal, teraktivasi: true, konversi: true);
    }

    /**
     * @param  list<array<string, mixed>>  $perChannel
     * @return array<string, mixed>
     */
    private function cariChannel(array $perChannel, string $channel): array
    {
        $baris = $this->cariChannelAtauNull($perChannel, $channel);

        $this->assertNotNull($baris, "Channel {$channel} tidak ada di laporan CAC.");

        return $baris;
    }

    /**
     * @param  list<array<string, mixed>>  $perChannel
     * @return array<string, mixed>|null
     */
    private function cariChannelAtauNull(array $perChannel, string $channel): ?array
    {
        foreach ($perChannel as $satu) {
            if ($satu['Channel'] === $channel) {
                return $satu;
            }
        }

        return null;
    }

    private function filter(): FilterGrowth
    {
        return new FilterGrowth(
            CarbonImmutable::parse($this->dari())->startOfDay(),
            CarbonImmutable::parse($this->sampai())->endOfDay(),
        );
    }

    private function dari(): string
    {
        return CarbonImmutable::now()->subDays(30)->toDateString();
    }

    private function sampai(): string
    {
        return CarbonImmutable::now()->addDay()->toDateString();
    }
}
