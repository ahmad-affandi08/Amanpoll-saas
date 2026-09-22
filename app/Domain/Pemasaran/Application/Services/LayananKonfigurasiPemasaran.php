<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Application\Services;

use App\Domain\Pemasaran\Domain\KatalogKonfigurasiPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\KonfigurasiPemasaran;
use App\Shared\Domain\Exceptions\DataTidakDitemukan;
use Illuminate\Contracts\Cache\Repository as Cache;

/** Pembaca dan penulis konfigurasi pemasaran (MARKETING.md 30). */
final class LayananKonfigurasiPemasaran
{
    private const KUNCI_CACHE = 'konfigurasi-pemasaran';

    private const UMUR_DETIK = 300;

    public function __construct(private readonly Cache $cache) {}

    public function ambil(string $kunci): mixed
    {
        if (! KatalogKonfigurasiPemasaran::dikenal($kunci)) {
            throw new DataTidakDitemukan("Konfigurasi pemasaran {$kunci} tidak dikenal.");
        }

        $tersimpan = $this->semua();

        return array_key_exists($kunci, $tersimpan)
            ? $tersimpan[$kunci]
            : KatalogKonfigurasiPemasaran::bawaan($kunci);
    }

    public function angka(string $kunci): int
    {
        return (int) $this->ambil($kunci);
    }

    /** @return array<array-key, mixed> */
    public function daftar(string $kunci): array
    {
        $nilai = $this->ambil($kunci);

        return is_array($nilai) ? $nilai : [];
    }

    public function simpan(string $kunci, mixed $nilai): void
    {
        if (! KatalogKonfigurasiPemasaran::dikenal($kunci)) {
            throw new DataTidakDitemukan("Konfigurasi pemasaran {$kunci} tidak dikenal.");
        }

        KonfigurasiPemasaran::query()->updateOrCreate(
            ['Kunci' => $kunci],
            ['Nilai' => $nilai, 'Keterangan' => KatalogKonfigurasiPemasaran::keterangan($kunci)],
        );

        $this->cache->forget(self::KUNCI_CACHE);
    }

    /** @return array<string, mixed> */
    public function semua(): array
    {
        /** @var array<string, mixed> $nilai */
        $nilai = $this->cache->remember(
            self::KUNCI_CACHE,
            self::UMUR_DETIK,
            fn (): array => KonfigurasiPemasaran::query()->pluck('Nilai', 'Kunci')->all(),
        );

        return $nilai;
    }

    /**
     * Seluruh setelan beserta nilai efektifnya, untuk halaman Pengaturan.
     *
     * @return array<string, mixed>
     */
    public function efektif(): array
    {
        $hasil = [];
        foreach (KatalogKonfigurasiPemasaran::kunci() as $kunci) {
            $hasil[$kunci] = $this->ambil($kunci);
        }

        return $hasil;
    }
}
