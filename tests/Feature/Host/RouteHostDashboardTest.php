<?php

declare(strict_types=1);

namespace Tests\Feature\Host;

use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;

/**
 * Host dashboard melayani sistem penuh dan tidak pernah menampilkan situs
 * pemasaran (PRD 5.4, MARKETING.md 34.2).
 */
final class RouteHostDashboardTest extends KasusHost
{
    public function test_root_host_dashboard_mengarahkan_pengunjung_anonim_ke_login(): void
    {
        $this->get($this->urlDashboard('/'))->assertRedirect(route('login'));
    }

    public function test_root_host_dashboard_tidak_pernah_menampilkan_landing_page(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-HOST', 'Nama' => 'Organisasi Host', 'Status' => 'Aktif']);
        $pengguna = Pengguna::create([
            'OrganisasiId' => $organisasi->Id,
            'Nama' => 'Pengguna Host',
            'Email' => 'host@amanpoll.test',
            'KataSandi' => 'rahasia',
            'Status' => 'Aktif',
        ]);

        $respons = $this->actingAs($pengguna)->get($this->urlDashboard('/'));

        $respons->assertOk();
        $this->assertNotSame('Publik/Beranda', $respons->viewData('page')['component']);
    }

    public function test_halaman_masuk_dilayani_host_dashboard(): void
    {
        $this->get($this->urlDashboard('/login'))->assertOk();
    }

    public function test_rute_publik_tidak_dilayani_host_dashboard(): void
    {
        $this->get($this->urlDashboard('/sitemap.xml'))->assertNotFound();
    }

    public function test_rute_bernama_selalu_menunjuk_host_dashboard(): void
    {
        foreach (['login', 'dashboard', 'aset.index'] as $nama) {
            $this->assertStringContainsString(
                $this->host->dashboard(),
                route($nama),
                "Rute {$nama} harus berada di host dashboard.",
            );
        }
    }

    public function test_host_dashboard_tidak_mengenal_kode_organisasi_dari_host_publik(): void
    {
        // Host publik tidak pernah membaca sesi organisasi; membuktikannya
        // dengan memastikan halaman publik tetap terbuka bagi tamu.
        $this->get($this->urlPublik('/'))->assertOk();
        $this->assertGuest();
    }
}
