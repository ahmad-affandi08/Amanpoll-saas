<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Pemasaran;

use App\Core\Host\PetaHost;
use App\Domain\Pemasaran\Domain\KatalogPeristiwaPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\EventPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Kampanye;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\SesiPengunjung;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\UtmPemasaran;
use App\Http\Middleware\TetapkanSesiPengunjung;
use Illuminate\Support\Str;

/** Penangkapan UTM dan sesi kunjungan (MARKETING.md 14, 36). */
final class UtmTersimpanTest extends KasusPemasaran
{
    private function urlPublik(string $path = '/'): string
    {
        return 'http://'.(string) app(PetaHost::class)->publik().'/'.ltrim($path, '/');
    }

    public function test_kunjungan_merekam_sesi_dan_peristiwa_halaman_dilihat(): void
    {
        $this->get($this->urlPublik('/'))->assertOk();

        $this->assertSame(1, SesiPengunjung::query()->count());
        $this->assertTrue(
            EventPemasaran::query()->where('Jenis', KatalogPeristiwaPemasaran::HALAMAN_DILIHAT)->exists(),
        );
    }

    public function test_utm_tersimpan_dari_query_string(): void
    {
        $this->get($this->urlPublik('/?utm_source=google&utm_medium=cpc&utm_campaign=demo-q1&utm_term=cmms'))
            ->assertOk();

        $utm = UtmPemasaran::query()->firstOrFail();

        $this->assertSame('google', $utm->Source);
        $this->assertSame('cpc', $utm->Medium);
        $this->assertSame('demo-q1', $utm->Campaign);
        $this->assertSame('cmms', $utm->Term);
    }

    public function test_utm_tertaut_ke_kampanye_yang_kodenya_cocok(): void
    {
        $kampanye = Kampanye::create([
            'Kode' => 'demo-q1',
            'Nama' => 'Demo Kuartal 1',
            'Objective' => 'Lead',
            'Status' => 'Aktif',
        ]);

        $this->get($this->urlPublik('/?utm_campaign=demo-q1'))->assertOk();

        $this->assertSame($kampanye->Id, UtmPemasaran::query()->value('KampanyeId'));
    }

    public function test_kampanye_yang_belum_terdaftar_tetap_tersimpan_apa_adanya(): void
    {
        $this->get($this->urlPublik('/?utm_campaign=belum-terdaftar'))->assertOk();

        $utm = UtmPemasaran::query()->firstOrFail();

        $this->assertSame('belum-terdaftar', $utm->Campaign);
        $this->assertNull($utm->KampanyeId);
    }

    public function test_pengenal_cookie_yang_tidak_berbentuk_ulid_diabaikan(): void
    {
        $respons = $this->withCookie(TetapkanSesiPengunjung::NAMA_COOKIE, '../../etc/passwd')
            ->get($this->urlPublik('/'));

        $respons->assertOk();
        $this->assertTrue(Str::isUlid((string) SesiPengunjung::query()->value('PengenalPengunjung')));
    }
}
