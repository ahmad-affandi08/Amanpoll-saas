<?php

declare(strict_types=1);

namespace App\Core\Audit;

/**
 * Wadah singleton per-request untuk ID korelasi, ditetapkan
 * TetapkanKorelasiId middleware, dibaca LayananAudit.
 */
final class KorelasiId
{
    private ?string $nilai = null;

    public function tetapkan(string $nilai): void
    {
        $this->nilai = $nilai;
    }

    public function ambil(): ?string
    {
        return $this->nilai;
    }
}
