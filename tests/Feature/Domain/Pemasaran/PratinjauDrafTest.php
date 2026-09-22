<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Pemasaran;

use App\Domain\Pemasaran\Infrastructure\Persistence\Models\HalamanPemasaran;
use Illuminate\Support\Facades\URL;

/** Draf tidak dapat diakses tanpa tanda tangan dan tidak terindeks (MARKETING.md 8, TASK 32.07). */
final class PratinjauDrafTest extends KasusHalaman
{
    public function test_pratinjau_tanpa_tanda_tangan_ditolak(): void
    {
        $halaman = $this->buatDraf('/harga');

        $this->get($this->urlPublik("/pratinjau/{$halaman->Id}/{$halaman->VersiDrafId}"))
            ->assertForbidden();
    }

    public function test_pratinjau_dengan_tanda_tangan_menampilkan_draf(): void
    {
        $halaman = $this->buatDraf('/harga', 'Judul Draf');

        $respons = $this->get($this->tautanPratinjau());

        $respons->assertOk();
        $this->assertSame('Publik/Halaman', $respons->viewData('page')['component']);
        $this->assertSame('Judul Draf', $respons->viewData('page')['props']['halaman']['Judul']);
        $this->assertTrue($respons->viewData('page')['props']['halaman']['Pratinjau']);
        $this->assertNotNull($halaman->VersiDrafId);
    }

    public function test_pratinjau_tidak_boleh_diindeks(): void
    {
        $this->buatDraf('/harga');

        $this->get($this->tautanPratinjau())
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    public function test_prop_noindex_ikut_terkirim_ke_halaman(): void
    {
        $this->buatDraf('/harga');

        $props = $this->get($this->tautanPratinjau())->viewData('page')['props'];

        $this->assertTrue($props['halaman']['NoIndex']);
    }

    public function test_tanda_tangan_kedaluwarsa_ditolak(): void
    {
        $this->buatDraf('/harga');
        $tautan = $this->tautanPratinjau();

        $this->travel(2)->hours();

        $this->get($tautan)->assertForbidden();
    }

    public function test_tanda_tangan_yang_diubah_ditolak(): void
    {
        $this->buatDraf('/harga');
        $tautan = $this->tautanPratinjau();

        $this->get($tautan.'&sisipan=1')->assertForbidden();
    }

    public function test_pratinjau_versi_milik_halaman_lain_ditolak(): void
    {
        $satu = $this->buatDraf('/harga');
        $dua = $this->buatDraf('/demo', 'Demo');

        $tautan = URL::temporarySignedRoute('publik.pratinjau', now()->addHour(), [
            'halaman' => $satu->Id,
            'versi' => $dua->VersiDrafId,
        ]);

        $this->get($tautan)->assertNotFound();
    }

    public function test_draf_tidak_masuk_sitemap(): void
    {
        $this->buatDraf('/harga');

        $isi = $this->get($this->urlPublik('/sitemap.xml'))->getContent() ?: '';

        $this->assertStringNotContainsString('/harga</loc>', $isi);
        $this->assertStringNotContainsString('/pratinjau/', $isi);
    }

    private function tautanPratinjau(string $slug = '/harga'): string
    {
        $halaman = HalamanPemasaran::query()
            ->where('Slug', $slug)
            ->firstOrFail();

        return URL::temporarySignedRoute('publik.pratinjau', now()->addHour(), [
            'halaman' => $halaman->Id,
            'versi' => $halaman->VersiDrafId,
        ]);
    }
}
