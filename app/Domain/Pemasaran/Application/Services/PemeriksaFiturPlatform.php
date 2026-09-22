<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Application\Services;

use App\Domain\Pemasaran\Domain\KatalogFiturPlatform;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\FiturPlatform;
use Illuminate\Contracts\Cache\Repository as Cache;

/**
 * Sumber kebenaran tunggal untuk feature flag platform (MARKETING.md 31).
 *
 * Gerbang rute, menu, dan prop yang dikirim ke UI semuanya bertanya ke sini,
 * sehingga tidak mungkin ada jalur yang memakai jawaban berbeda — menu yang
 * hilang tetapi rutenya terbuka adalah bentuk kebocoran yang paling mudah
 * terjadi bila jawabannya dihitung di dua tempat.
 */
final class PemeriksaFiturPlatform
{
    private const KUNCI_CACHE = 'fitur-platform';

    private const UMUR_DETIK = 300;

    public function __construct(private readonly Cache $cache) {}

    public function aktif(string $kode): bool
    {
        // Kode yang tidak dikenal katalog selalu mati: salah ketik pada gerbang
        // rute lebih baik menutup halaman daripada membukanya diam-diam.
        if (! KatalogFiturPlatform::dikenal($kode)) {
            return false;
        }

        return $this->status()[$kode] ?? false;
    }

    /** @return array<string, bool> */
    public function status(): array
    {
        /** @var array<string, bool> $status */
        $status = $this->cache->remember(
            self::KUNCI_CACHE,
            self::UMUR_DETIK,
            fn (): array => $this->dariDatabase(),
        );

        return $status;
    }

    public function bersihkanCache(): void
    {
        $this->cache->forget(self::KUNCI_CACHE);
    }

    /** @return array<string, bool> */
    private function dariDatabase(): array
    {
        $tersimpan = FiturPlatform::query()->pluck('Aktif', 'Kode');

        $status = [];
        foreach (KatalogFiturPlatform::kode() as $kode) {
            // Flag yang belum pernah disemai dianggap mati, bukan hidup: modul
            // baru tidak boleh menyala sendiri saat deployment.
            $status[$kode] = (bool) ($tersimpan[$kode] ?? false);
        }

        return $status;
    }
}
