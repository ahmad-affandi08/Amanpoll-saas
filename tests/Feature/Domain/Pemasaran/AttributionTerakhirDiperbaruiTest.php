<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Pemasaran;

use App\Core\Host\PetaHost;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\AttributionPemasaran;
use App\Http\Middleware\TetapkanSesiPengunjung;

/** Last touch selalu mengikuti kedatangan terbaru (MARKETING.md 14). */
final class AttributionTerakhirDiperbaruiTest extends KasusPemasaran
{
    private function urlPublik(string $path = '/'): string
    {
        return 'http://'.(string) app(PetaHost::class)->publik().'/'.ltrim($path, '/');
    }

    public function test_last_touch_diperbarui_kunjungan_berikutnya(): void
    {
        $pertama = $this->get($this->urlPublik('/?utm_source=google&utm_campaign=awal'));
        $pengenal = (string) $pertama->getCookie(TetapkanSesiPengunjung::NAMA_COOKIE)?->getValue();

        $this->withCookie(TetapkanSesiPengunjung::NAMA_COOKIE, $pengenal)
            ->get($this->urlPublik('/?utm_source=linkedin&utm_campaign=akhir'))
            ->assertOk();

        $attribution = AttributionPemasaran::query()->where('PengenalPengunjung', $pengenal)->firstOrFail();

        $this->assertSame('linkedin', $attribution->SumberTerakhir);
        $this->assertSame('akhir', $attribution->KampanyeTerakhir);
    }
}
