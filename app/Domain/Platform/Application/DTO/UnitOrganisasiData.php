<?php

declare(strict_types=1);

namespace App\Domain\Platform\Application\DTO;

final readonly class UnitOrganisasiData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $IndukId = null,
        public mixed $Kode = null,
        public mixed $Nama = null,
        public mixed $Jenis = null,
        public mixed $Email = null,
        public mixed $Telepon = null,
        public mixed $Status = null,
        public mixed $Urutan = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            IndukId: $data['IndukId'] ?? null,
            Kode: $data['Kode'] ?? null,
            Nama: $data['Nama'] ?? null,
            Jenis: $data['Jenis'] ?? null,
            Email: $data['Email'] ?? null,
            Telepon: $data['Telepon'] ?? null,
            Status: $data['Status'] ?? null,
            Urutan: $data['Urutan'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
