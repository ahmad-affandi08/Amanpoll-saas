<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Application\DTO;

final readonly class PenyediaPermintaanPenawaranData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $PermintaanPenawaranId = null,
        public mixed $PenyediaId = null,
        public mixed $DikirimPada = null,
        public mixed $DilihatPada = null,
        public mixed $Status = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            PermintaanPenawaranId: $data['PermintaanPenawaranId'] ?? null,
            PenyediaId: $data['PenyediaId'] ?? null,
            DikirimPada: $data['DikirimPada'] ?? null,
            DilihatPada: $data['DilihatPada'] ?? null,
            Status: $data['Status'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
