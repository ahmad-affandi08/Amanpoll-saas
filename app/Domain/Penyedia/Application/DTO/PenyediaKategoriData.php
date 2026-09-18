<?php

declare(strict_types=1);

namespace App\Domain\Penyedia\Application\DTO;

final readonly class PenyediaKategoriData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $PenyediaId = null,
        public mixed $KategoriPenyediaId = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            PenyediaId: $data['PenyediaId'] ?? null,
            KategoriPenyediaId: $data['KategoriPenyediaId'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
