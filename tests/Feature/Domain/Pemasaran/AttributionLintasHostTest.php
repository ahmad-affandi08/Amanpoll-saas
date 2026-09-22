<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Pemasaran;

use App\Core\Host\PetaHost;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\AttributionPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\SesiPengunjung;
use App\Http\Middleware\TetapkanSesiPengunjung;
use Illuminate\Support\Str;

/**
 * Identitas pengunjung bertahan saat berpindah host (MARKETING.md 1.1, 14).
 */
final class AttributionLintasHostTest extends KasusPemasaran
{
    private function urlPublik(string $path = '/'): string
    {
        return 'http://'.(string) app(PetaHost::class)->publik().'/'.ltrim($path, '/');
    }

    public function test_first_touch_bertahan_saat_pengunjung_berpindah_ke_host_dashboard(): void
    {
        $publik = $this->get($this->urlPublik('/?utm_source=google&utm_campaign=lintas-host'));
        $pengenal = (string) $publik->getCookie(TetapkanSesiPengunjung::NAMA_COOKIE)?->getValue();

        // Perjalanan yang sesungguhnya: pengunjung mengklik CTA lalu mendarat di
        // halaman masuk pada host dashboard.
        $this->withCookie(TetapkanSesiPengunjung::NAMA_COOKIE, $pengenal)
            ->get(route('login'))
            ->assertOk();

        $attribution = AttributionPemasaran::query()->where('PengenalPengunjung', $pengenal)->firstOrFail();

        $this->assertSame('google', $attribution->SumberPertama);
        $this->assertSame('lintas-host', $attribution->KampanyePertama);
    }

    public function test_serah_terima_lewat_parameter_dipakai_saat_cookie_belum_ada(): void
    {
        $props = $this->get($this->urlPublik('/'))->viewData('page')['props'];

        // Host uji tidak berbagi domain induk, jadi tautannya menitipkan
        // pengenal sekali lewat parameter.
        $this->assertStringContainsString(
            TetapkanSesiPengunjung::PARAMETER_SERAH_TERIMA.'=',
            (string) $props['urlMasuk'],
        );
    }

    public function test_pengenal_hasil_serah_terima_dipindahkan_ke_cookie(): void
    {
        $pengenal = (string) Str::ulid();

        $respons = $this->get(route('login').'?'.TetapkanSesiPengunjung::PARAMETER_SERAH_TERIMA.'='.$pengenal);

        $respons->assertOk();
        $this->assertSame(
            $pengenal,
            $respons->getCookie(TetapkanSesiPengunjung::NAMA_COOKIE)?->getValue(),
        );
    }

    public function test_serah_terima_tidak_dapat_menimpa_pengunjung_yang_sudah_punya_riwayat(): void
    {
        $milikSendiri = (string) Str::ulid();
        $milikOrangLain = (string) Str::ulid();

        $respons = $this->withCookie(TetapkanSesiPengunjung::NAMA_COOKIE, $milikSendiri)
            ->get($this->urlPublik('/?'.TetapkanSesiPengunjung::PARAMETER_SERAH_TERIMA.'='.$milikOrangLain));

        $respons->assertOk();
        $this->assertSame($milikSendiri, SesiPengunjung::query()->value('PengenalPengunjung'));
    }
}
