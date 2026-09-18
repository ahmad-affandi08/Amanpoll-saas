<?php

declare(strict_types=1);

namespace App\Domain\IntegrasiAudit\Application\DTO;

final readonly class PanggilanBalikWebData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $Nama = null,
        public mixed $Url = null,
        public mixed $Rahasia = null,
        public mixed $Peristiwa = null,
        public mixed $Aktif = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            Nama: $data['Nama'] ?? null,
            Url: $data['Url'] ?? null,
            Rahasia: $data['Rahasia'] ?? null,
            Peristiwa: $data['Peristiwa'] ?? null,
            Aktif: $data['Aktif'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
