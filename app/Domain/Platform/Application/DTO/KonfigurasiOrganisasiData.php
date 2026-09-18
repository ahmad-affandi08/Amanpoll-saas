<?php

declare(strict_types=1);

namespace App\Domain\Platform\Application\DTO;

final readonly class KonfigurasiOrganisasiData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $Kunci = null,
        public mixed $Nilai = null,
        public mixed $Rahasia = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            Kunci: $data['Kunci'] ?? null,
            Nilai: $data['Nilai'] ?? null,
            Rahasia: $data['Rahasia'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
