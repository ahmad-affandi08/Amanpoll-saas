<?php

declare(strict_types=1);

namespace App\Domain\Aset\Application\DTO;

final readonly class MerekData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $Nama = null,
        public mixed $NegaraAsal = null,
        public mixed $Website = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            Nama: $data['Nama'] ?? null,
            NegaraAsal: $data['NegaraAsal'] ?? null,
            Website: $data['Website'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
