<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Pemasaran;

use App\Core\Host\PetaHost;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\AttributionPemasaran;
use App\Http\Middleware\TetapkanSesiPengunjung;

/** First touch ditulis sekali dan tidak pernah tertimpa (MARKETING.md 14). */
final class AttributionPertamaTerjagaTest extends KasusPemasaran
{
    private function urlPublik(string $path = '/'): string
    {
        return 'http://'.(string) app(PetaHost::class)->publik().'/'.ltrim($path, '/');
    }

    public function test_first_touch_tidak_tertimpa_kunjungan_berikutnya(): void
    {
        $pertama = $this->get($this->urlPublik('/?utm_source=google&utm_medium=cpc&utm_campaign=awal'));
        $pengenal = (string) $pertama->getCookie(TetapkanSesiPengunjung::NAMA_COOKIE)?->getValue();

        $this->withCookie(TetapkanSesiPengunjung::NAMA_COOKIE, $pengenal)
            ->get($this->urlPublik('/?utm_source=linkedin&utm_medium=social&utm_campaign=akhir'))
            ->assertOk();

        $attribution = AttributionPemasaran::query()->where('PengenalPengunjung', $pengenal)->firstOrFail();

        $this->assertSame('google', $attribution->SumberPertama);
        $this->assertSame('awal', $attribution->KampanyePertama);
    }

    public function test_kunjungan_tanpa_riwayat_dicatat_sebagai_direct(): void
    {
        $this->get($this->urlPublik('/'))->assertOk();

        $attribution = AttributionPemasaran::query()->firstOrFail();

        $this->assertSame('direct', $attribution->SumberPertama);
        $this->assertSame('direct', $attribution->MediumPertama);
    }

    public function test_kunjungan_dengan_referrer_mencatat_host_perujuknya(): void
    {
        $this->withHeader('referer', 'https://www.contoh-berita.test/artikel')
            ->get($this->urlPublik('/'))
            ->assertOk();

        $attribution = AttributionPemasaran::query()->firstOrFail();

        $this->assertSame('www.contoh-berita.test', $attribution->SumberPertama);
        $this->assertSame('referral', $attribution->MediumPertama);
    }
}
