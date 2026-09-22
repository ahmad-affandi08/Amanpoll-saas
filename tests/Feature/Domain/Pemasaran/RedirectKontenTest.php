<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Pemasaran;

use App\Domain\Pemasaran\Application\Actions\SimpanDrafKonten;
use App\Domain\Pemasaran\Application\Actions\TerbitkanKonten;
use App\Domain\Pemasaran\Application\Services\PencariRedirectPemasaran;
use App\Domain\Pemasaran\Domain\Enums\JenisKontenPemasaran;
use App\Domain\Pemasaran\Domain\Enums\KodeRedirect;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\KontenPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\RedirectPemasaran;

/** Alamat konten yang pernah terbit tidak pernah mati diam-diam (Gate 38.04). */
final class RedirectKontenTest extends KasusKonten
{
    /** @param array<string, mixed> $tambahan */
    private function pindahkan(
        KontenPemasaran $konten,
        string $slug,
        JenisKontenPemasaran $jenis = JenisKontenPemasaran::Artikel,
        array $tambahan = [],
    ): KontenPemasaran {
        return app(SimpanDrafKonten::class)->jalankan($konten, [
            'Slug' => $slug,
            'Jenis' => $jenis->value,
            'Judul' => $konten->Judul,
            'IsiMarkdown' => 'Naskah.',
            ...$tambahan,
        ]);
    }

    public function test_memindahkan_slug_konten_terbit_membuat_redirect_301(): void
    {
        $konten = $this->buatTerbit('panduan-lama');
        $this->pindahkan($konten, 'panduan-baru');

        $redirect = RedirectPemasaran::query()->where('Dari', '/artikel/panduan-lama')->first();

        $this->assertNotNull($redirect);
        $this->assertSame(KodeRedirect::Permanen, $redirect->Kode);
        $this->assertSame('/artikel/panduan-baru', $redirect->Ke);
        $this->assertTrue($redirect->Aktif);
    }

    public function test_alamat_lama_benar_benar_mengalihkan(): void
    {
        $konten = $this->buatTerbit('panduan-lama');
        $konten = $this->pindahkan($konten, 'panduan-baru');
        app(TerbitkanKonten::class)->jalankan($konten);

        $this->get($this->urlPublik('/artikel/panduan-lama'))
            ->assertStatus(301)
            ->assertRedirect($this->urlPublik('/artikel/panduan-baru'));
    }

    /** Berpindah rak juga berpindah alamat, jadi ia pun butuh redirect. */
    public function test_mengganti_jenis_konten_terbit_juga_membuat_redirect(): void
    {
        $konten = $this->buatTerbit('panduan-cmms');
        $this->pindahkan($konten, 'panduan-cmms', JenisKontenPemasaran::Panduan);

        $redirect = RedirectPemasaran::query()->where('Dari', '/artikel/panduan-cmms')->first();

        $this->assertNotNull($redirect);
        $this->assertSame('/panduan/panduan-cmms', $redirect->Ke);
    }

    /** Konten yang belum pernah terbit tidak punya alamat yang perlu diselamatkan. */
    public function test_memindahkan_slug_draf_tidak_membuat_redirect(): void
    {
        $konten = $this->buatDraf('draf-lama');
        $this->pindahkan($konten, 'draf-baru');

        $this->assertSame(0, RedirectPemasaran::query()->count());
    }

    /**
     * Peta redirect berjalan sebelum konten dicari, jadi alamat yang dihuni
     * kembali harus melepaskan redirect lamanya.
     */
    public function test_kembali_ke_alamat_lama_mematikan_redirectnya(): void
    {
        $konten = $this->buatTerbit('panduan-lama');
        $konten = $this->pindahkan($konten, 'panduan-baru');
        app(TerbitkanKonten::class)->jalankan($konten);

        $konten = $this->pindahkan($konten->fresh(), 'panduan-lama');
        app(TerbitkanKonten::class)->jalankan($konten);

        $this->assertFalse(
            (bool) RedirectPemasaran::query()->where('Dari', '/artikel/panduan-lama')->value('Aktif'),
        );
        $this->get($this->urlPublik('/artikel/panduan-lama'))->assertOk();
    }

    /** Peta redirect disimpan sementara, jadi cachenya harus ikut dibuang. */
    public function test_cache_redirect_dibuang_saat_slug_dipindahkan(): void
    {
        $konten = $this->buatTerbit('panduan-lama');

        // Memanaskan cache lebih dulu, persis seperti perayap yang sudah lewat.
        app(PencariRedirectPemasaran::class)->cari('/artikel/panduan-lama');

        $konten = $this->pindahkan($konten, 'panduan-baru');
        app(TerbitkanKonten::class)->jalankan($konten);

        $this->assertNotNull(app(PencariRedirectPemasaran::class)->cari('/artikel/panduan-lama'));
    }

    /** 410 sudah didukung sejak peta redirect dibuat; konten yang ditarik memakainya. */
    public function test_redirect_410_menyatakan_konten_hilang_permanen(): void
    {
        RedirectPemasaran::create([
            'Dari' => '/artikel/sudah-dihapus',
            'Ke' => null,
            'Kode' => KodeRedirect::Hilang,
            'Aktif' => true,
        ]);

        $this->get($this->urlPublik('/artikel/sudah-dihapus'))->assertStatus(410);
    }

    public function test_redirect_302_tetap_sementara(): void
    {
        $this->buatTerbit('panduan-baru');

        RedirectPemasaran::create([
            'Dari' => '/artikel/uji-coba',
            'Ke' => '/artikel/panduan-baru',
            'Kode' => KodeRedirect::Sementara,
            'Aktif' => true,
        ]);

        $this->get($this->urlPublik('/artikel/uji-coba'))->assertStatus(302);
    }

    public function test_redirect_nonaktif_tidak_dipakai(): void
    {
        $this->buatTerbit('panduan-baru');

        RedirectPemasaran::create([
            'Dari' => '/artikel/uji-coba',
            'Ke' => '/artikel/panduan-baru',
            'Kode' => KodeRedirect::Permanen,
            'Aktif' => false,
        ]);

        $this->get($this->urlPublik('/artikel/uji-coba'))->assertNotFound();
    }
}
