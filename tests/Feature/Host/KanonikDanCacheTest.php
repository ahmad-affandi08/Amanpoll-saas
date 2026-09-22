<?php

declare(strict_types=1);

namespace Tests\Feature\Host;

/**
 * Satu halaman publik hanya boleh punya satu URL, dan isinya yang sama bagi
 * semua orang tidak dihitung ulang tiap permintaan (MARKETING.md 1, 1.2).
 */
final class KanonikDanCacheTest extends KasusHost
{
    public function test_bentuk_www_dialihkan_301_ke_bentuk_kanonik(): void
    {
        $respons = $this->get('http://www.'.$this->host->publik().'/');

        $respons->assertStatus(301);
        $respons->assertRedirect('http://'.$this->host->publikKanonik().'/');
    }

    public function test_bentuk_kanonik_tidak_dialihkan(): void
    {
        $this->get($this->urlPublik('/'))->assertOk();
    }

    public function test_pengalihan_mempertahankan_path_dan_query(): void
    {
        $respons = $this->get('http://www.'.$this->host->publik().'/robots.txt?a=1');

        $respons->assertStatus(301);
        $respons->assertRedirect('http://'.$this->host->publikKanonik().'/robots.txt?a=1');
    }

    public function test_halaman_publik_menyatakan_url_kanoniknya(): void
    {
        $props = $this->get($this->urlPublik('/'))->viewData('page')['props'];

        $this->assertSame('http://'.$this->host->publikKanonik().'/', $props['kanonik']);
    }

    public function test_robots_dilayani_dari_cache_pada_permintaan_kedua(): void
    {
        $this->get($this->urlPublik('/robots.txt'))
            ->assertOk()
            ->assertHeader('X-Cache-Amanpoll', 'miss');

        $this->get($this->urlPublik('/robots.txt'))
            ->assertOk()
            ->assertHeader('X-Cache-Amanpoll', 'hit');
    }

    public function test_respons_cache_tidak_membawa_cookie_pengunjung_orang_lain(): void
    {
        $pertama = $this->get($this->urlPublik('/robots.txt'));
        $kedua = $this->get($this->urlPublik('/robots.txt'));

        $this->assertNotSame(
            $pertama->headers->get('Set-Cookie'),
            null,
            'Permintaan pertama tetap membentuk cookie pengunjung.',
        );

        $kedua->assertHeader('X-Cache-Amanpoll', 'hit');
        $this->assertStringNotContainsString(
            (string) $pertama->getCookie('amanpoll_pengunjung')?->getValue(),
            (string) $kedua->headers->get('Set-Cookie'),
        );
    }
}
