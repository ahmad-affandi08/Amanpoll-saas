<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Domain\ValueObjects;

use App\Domain\Pemasaran\Infrastructure\Persistence\Models\PengirimanFormulir;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Prospek;

/** Hasil satu pengiriman formulir publik (MARKETING.md 10). */
final readonly class HasilPengirimanFormulir
{
    private function __construct(
        public bool $ditolakSebagaiSpam,
        public ?Prospek $prospek = null,
        public ?PengirimanFormulir $pengiriman = null,
    ) {}

    public static function tersimpan(Prospek $prospek, PengirimanFormulir $pengiriman): self
    {
        return new self(false, $prospek, $pengiriman);
    }

    public static function spam(): self
    {
        return new self(true);
    }
}
