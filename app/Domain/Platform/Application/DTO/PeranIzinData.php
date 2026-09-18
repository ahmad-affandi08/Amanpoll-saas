<?php

declare(strict_types=1);

namespace App\Domain\Platform\Application\DTO;

final readonly class PeranIzinData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $PeranId = null,
        public mixed $IzinId = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            PeranId: $data['PeranId'] ?? null,
            IzinId: $data['IzinId'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
