<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Application\Services;

use App\Domain\Pemasaran\Infrastructure\Persistence\Models\RedirectPemasaran;
use Illuminate\Contracts\Cache\Repository as Cache;

/**
 * Peta redirect situs publik (MARKETING.md 9).
 *
 * Seluruh aturan aktif dibaca sekali lalu disimpan sebagai satu peta. Bentuk
 * ini dipilih karena middlewarenya berjalan pada setiap permintaan halaman
 * publik, termasuk yang tidak punya redirect sama sekali — dan satu query per
 * permintaan hanya untuk mendapati bahwa tidak ada apa-apa adalah harga yang
 * dibayar setiap pengunjung.
 */
final class PencariRedirectPemasaran
{
    private const KUNCI_CACHE = 'pemasaran:redirect';

    private const UMUR_DETIK = 300;

    public function __construct(private readonly Cache $cache) {}

    public function cari(string $jalur): ?RedirectPemasaran
    {
        $peta = $this->peta();
        $normal = self::normalkan($jalur);

        if (! isset($peta[$normal])) {
            return null;
        }

        return RedirectPemasaran::query()->find($peta[$normal]);
    }

    public function buangCache(): void
    {
        $this->cache->forget(self::KUNCI_CACHE);
    }

    /**
     * Jalur dinormalkan menjadi berawalan `/` dan tanpa garis miring penutup,
     * supaya `/harga`, `harga`, dan `/harga/` adalah aturan yang sama. Akar
     * situs tetap `/`.
     */
    public static function normalkan(string $jalur): string
    {
        $bersih = '/'.trim(parse_url($jalur, PHP_URL_PATH) ?: $jalur, '/');

        return $bersih === '/' ? '/' : rtrim($bersih, '/');
    }

    /** @return array<string, string> jalur => Id */
    private function peta(): array
    {
        /** @var array<string, string> $peta */
        $peta = $this->cache->remember(
            self::KUNCI_CACHE,
            self::UMUR_DETIK,
            fn (): array => RedirectPemasaran::query()
                ->where('Aktif', true)
                ->pluck('Id', 'Dari')
                ->all(),
        );

        return $peta;
    }
}
