<?php

declare(strict_types=1);

namespace Tests\Feature\Host;

use App\Http\Middleware\TetapkanSesiPengunjung;

/**
 * Host publik melayani situs pemasaran, anonim, dan tidak pernah menyentuh
 * rute sistem (MARKETING.md 1 dan 34.1).
 */
final class RouteHostPublikTest extends KasusHost
{
    public function test_root_host_publik_membuka_landing_page(): void
    {
        $respons = $this->get($this->urlPublik('/'));

        $respons->assertOk();
        $this->assertSame('Publik/Beranda', $respons->viewData('page')['component']);
    }

    public function test_landing_page_tidak_menuntut_autentikasi(): void
    {
        $this->assertGuest();

        $this->get($this->urlPublik('/'))->assertOk();
    }

    public function test_rute_sistem_tidak_dilayani_host_publik(): void
    {
        $this->get($this->urlPublik('/aset'))->assertNotFound();
        $this->get($this->urlPublik('/login'))->assertNotFound();
    }

    public function test_host_publik_boleh_diindeks(): void
    {
        $this->get($this->urlPublik('/'))
            ->assertOk()
            ->assertHeaderMissing('X-Robots-Tag');
    }

    public function test_robots_host_publik_mengizinkan_perayapan_dan_menunjuk_sitemap(): void
    {
        $respons = $this->get($this->urlPublik('/robots.txt'));

        $respons->assertOk();
        $this->assertStringContainsString('Allow: /', $respons->getContent() ?: '');
        $this->assertStringContainsString('Sitemap: ', $respons->getContent() ?: '');
    }

    public function test_sitemap_hanya_memuat_url_host_publik(): void
    {
        $respons = $this->get($this->urlPublik('/sitemap.xml'));

        $respons->assertOk();
        $isi = $respons->getContent() ?: '';

        $this->assertStringContainsString('<loc>http://'.$this->host->publikKanonik().'/</loc>', $isi);
        $this->assertStringNotContainsString('<loc>http://'.$this->host->dashboard().'/', $isi);
    }

    public function test_kunjungan_pertama_menerima_cookie_pengunjung(): void
    {
        $respons = $this->get($this->urlPublik('/'));

        $respons->assertOk();
        $respons->assertCookie(TetapkanSesiPengunjung::NAMA_COOKIE);
    }

    public function test_cookie_pengunjung_tidak_diganti_pada_kunjungan_berikutnya(): void
    {
        $pertama = $this->get($this->urlPublik('/'));

        // Nilai yang sudah didekripsi: withCookie() mengenkripsinya kembali saat
        // mengirim, jadi mengoper blob mentah akan terenkripsi dua kali.
        $pengenal = $pertama->getCookie(TetapkanSesiPengunjung::NAMA_COOKIE)?->getValue();

        $this->assertNotNull($pengenal);

        $kedua = $this->withCookie(TetapkanSesiPengunjung::NAMA_COOKIE, (string) $pengenal)
            ->get($this->urlPublik('/'));

        $kedua->assertOk();
        $kedua->assertCookieMissing(TetapkanSesiPengunjung::NAMA_COOKIE);
    }

    public function test_tautan_aksi_mengarah_ke_host_dashboard(): void
    {
        $props = $this->get($this->urlPublik('/'))->viewData('page')['props'];

        $this->assertStringContainsString($this->host->dashboard(), (string) $props['urlMasuk']);
        $this->assertStringContainsString($this->host->dashboard(), (string) $props['urlDaftar']);
    }
}
