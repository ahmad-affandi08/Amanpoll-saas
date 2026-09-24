<?php

declare(strict_types=1);

namespace App\Domain\Platform\Application\Services;

use App\Domain\Platform\Domain\Contracts\DeskripsiPenyediaLayanan;
use App\Domain\Platform\Domain\Enums\KategoriPenyediaLayanan;
use Illuminate\Contracts\Container\Container;

/**
 * Daftar penyedia yang bisa dipilih di konsol platform (PRD 8.23).
 *
 * Domain pemakai mendaftarkan adapternya dengan tag `amanpoll.penyedia-layanan`,
 * sehingga domain Platform tidak perlu mengenal Langganan atau Pemasaran.
 */
final class KatalogPenyediaLayanan
{
    public const TAG = 'amanpoll.penyedia-layanan';

    /** Kredensial pembayaran, WhatsApp, dan email setara kunci brankas, jadi izinnya berdiri sendiri. */
    public const IZIN_KELOLA = 'platform.penyedia-layanan.kelola';

    /** @var list<DeskripsiPenyediaLayanan>|null */
    private ?array $penyedia = null;

    public function __construct(private readonly Container $container) {}

    /** @return list<DeskripsiPenyediaLayanan> */
    public function semua(): array
    {
        if ($this->penyedia === null) {
            $this->penyedia = [];

            foreach ($this->container->tagged(self::TAG) as $penyedia) {
                if ($penyedia instanceof DeskripsiPenyediaLayanan) {
                    $this->penyedia[] = $penyedia;
                }
            }
        }

        return $this->penyedia;
    }

    /** @return list<DeskripsiPenyediaLayanan> */
    public function menurutKategori(KategoriPenyediaLayanan $kategori): array
    {
        return array_values(array_filter(
            $this->semua(),
            fn (DeskripsiPenyediaLayanan $penyedia): bool => $penyedia->kategori() === $kategori,
        ));
    }

    public function untuk(KategoriPenyediaLayanan $kategori, string $kode): ?DeskripsiPenyediaLayanan
    {
        foreach ($this->menurutKategori($kategori) as $penyedia) {
            if ($penyedia->kode() === $kode) {
                return $penyedia;
            }
        }

        return null;
    }
}
