<?php

declare(strict_types=1);

namespace App\Core\Organisasi;

use LogicException;

final class KonteksOrganisasi
{
    private ?string $organisasiId = null;

    public function tetapkan(?string $organisasiId): void
    {
        $this->organisasiId = $organisasiId;
    }

    public function id(): ?string
    {
        return $this->organisasiId;
    }

    public function wajibId(): string
    {
        return $this->organisasiId ?? throw new LogicException('Konteks organisasi belum ditetapkan.');
    }

    public function ada(): bool
    {
        return $this->organisasiId !== null;
    }

    public function bersihkan(): void
    {
        $this->organisasiId = null;
    }
}
