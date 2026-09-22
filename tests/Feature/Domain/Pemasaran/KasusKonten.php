<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Pemasaran;

use App\Core\Host\PetaHost;
use App\Domain\Pemasaran\Application\Actions\SimpanDrafKonten;
use App\Domain\Pemasaran\Application\Actions\TerbitkanKonten;
use App\Domain\Pemasaran\Domain\Enums\JenisKontenPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\KontenPemasaran;

/** Landasan test CMS konten dan SEO manager. */
abstract class KasusKonten extends KasusPemasaran
{
    protected PetaHost $host;

    protected function setUp(): void
    {
        parent::setUp();
        $this->host = app(PetaHost::class);
    }

    protected function urlPublik(string $path = '/'): string
    {
        return 'http://'.(string) $this->host->publik().'/'.ltrim($path, '/');
    }

    /** @param array<string, mixed> $tambahan */
    protected function buatDraf(
        string $slug = 'panduan-cmms',
        string $judul = 'Panduan CMMS',
        JenisKontenPemasaran $jenis = JenisKontenPemasaran::Artikel,
        array $tambahan = [],
    ): KontenPemasaran {
        return app(SimpanDrafKonten::class)->jalankan(null, [
            'Slug' => $slug,
            'Jenis' => $jenis->value,
            'Judul' => $judul,
            'IsiMarkdown' => "## Pembuka\n\nIsi naskah {$judul}.",
            ...$tambahan,
        ]);
    }

    /** @param array<string, mixed> $tambahan */
    protected function buatTerbit(
        string $slug = 'panduan-cmms',
        string $judul = 'Panduan CMMS',
        JenisKontenPemasaran $jenis = JenisKontenPemasaran::Artikel,
        array $tambahan = [],
    ): KontenPemasaran {
        return app(TerbitkanKonten::class)->jalankan($this->buatDraf($slug, $judul, $jenis, $tambahan));
    }

    protected function isiSitemap(): string
    {
        return $this->get($this->urlPublik('/sitemap.xml'))->getContent() ?: '';
    }
}
