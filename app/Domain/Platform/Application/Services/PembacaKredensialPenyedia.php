<?php

declare(strict_types=1);

namespace App\Domain\Platform\Application\Services;

use App\Domain\Platform\Domain\Enums\KategoriPenyediaLayanan;
use App\Domain\Platform\Domain\ValueObjects\KredensialPenyedia;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenyediaLayananPlatform;

/**
 * Pintu baca kredensial penyedia untuk adapter WhatsApp dan pembayaran (PRD 8.23).
 *
 * Hanya penyedia yang `Aktif` yang terbaca. Tidak ada cache lintas permintaan, jadi
 * perubahan di konsol langsung berlaku pada permintaan berikutnya.
 */
final class PembacaKredensialPenyedia
{
    public function untuk(KategoriPenyediaLayanan $kategori, string $kode): ?KredensialPenyedia
    {
        $baris = PenyediaLayananPlatform::query()
            ->where('Kategori', $kategori->value)
            ->where('Kode', $kode)
            ->where('Aktif', true)
            ->first();

        return $baris?->keKredensial();
    }

    public function kodeUtama(KategoriPenyediaLayanan $kategori): ?string
    {
        $kode = PenyediaLayananPlatform::query()
            ->where('Kategori', $kategori->value)
            ->where('Aktif', true)
            ->where('Utama', true)
            ->value('Kode');

        return is_string($kode) ? $kode : null;
    }

    /** @return list<string> */
    public function kodeAktif(KategoriPenyediaLayanan $kategori): array
    {
        return PenyediaLayananPlatform::query()
            ->where('Kategori', $kategori->value)
            ->where('Aktif', true)
            ->orderByDesc('Utama')
            ->orderBy('Kode')
            ->pluck('Kode')
            ->map(fn (mixed $kode): string => (string) $kode)
            ->values()
            ->all();
    }
}
