<?php

declare(strict_types=1);

namespace Tests\Feature\Host;

/** Hanya host publik yang boleh diindeks (MARKETING.md 1.2). */
final class NoindexHostDashboardTest extends KasusHost
{
    public function test_halaman_host_dashboard_mengirim_noindex(): void
    {
        $this->get($this->urlDashboard('/login'))
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    public function test_pengalihan_host_dashboard_juga_mengirim_noindex(): void
    {
        $this->get($this->urlDashboard('/'))
            ->assertRedirect()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    public function test_endpoint_api_mengirim_noindex(): void
    {
        $this->getJson($this->urlDashboard('/api/v1/status'))
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    public function test_robots_host_dashboard_melarang_seluruh_perayapan(): void
    {
        $respons = $this->get($this->urlDashboard('/robots.txt'));

        $respons->assertOk();
        $isi = $respons->getContent() ?: '';

        $this->assertStringContainsString('Disallow: /', $isi);
        $this->assertStringNotContainsString('Allow: /', $isi);
    }

    public function test_host_publik_tidak_diberi_noindex(): void
    {
        $this->get($this->urlPublik('/'))
            ->assertOk()
            ->assertHeaderMissing('X-Robots-Tag');
    }
}
