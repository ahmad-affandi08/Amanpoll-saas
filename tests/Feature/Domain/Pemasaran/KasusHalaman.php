<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Pemasaran;

use App\Core\Host\PetaHost;
use App\Domain\Pemasaran\Application\Actions\SimpanDrafHalaman;
use App\Domain\Pemasaran\Application\Actions\TerbitkanHalaman;
use App\Domain\Pemasaran\Domain\Enums\JenisBlokHalaman;
use App\Domain\Pemasaran\Domain\Enums\TipeHalamanPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\HalamanPemasaran;

/** Landasan test halaman pemasaran. */
abstract class KasusHalaman extends KasusPemasaran
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

    /**
     * @param  list<array<string, mixed>>|null  $blok
     * @param  array<string, mixed>  $tambahan
     */
    protected function buatDraf(
        string $slug = '/harga',
        string $judul = 'Harga Amanpoll',
        ?array $blok = null,
        array $tambahan = [],
    ): HalamanPemasaran {
        return app(SimpanDrafHalaman::class)->jalankan(null, [
            'Slug' => $slug,
            'Tipe' => TipeHalamanPemasaran::Pricing->value,
            'Judul' => $judul,
            'Blok' => $blok ?? [[
                'Jenis' => JenisBlokHalaman::Hero->value,
                'Isi' => ['judul' => $judul],
            ]],
            ...$tambahan,
        ]);
    }

    /** @param list<array<string, mixed>>|null $blok */
    protected function buatTerbit(
        string $slug = '/harga',
        string $judul = 'Harga Amanpoll',
        ?array $blok = null,
    ): HalamanPemasaran {
        $halaman = $this->buatDraf($slug, $judul, $blok);

        return app(TerbitkanHalaman::class)->jalankan($halaman);
    }
}
