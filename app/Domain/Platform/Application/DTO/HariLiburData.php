<?php

declare(strict_types=1);

namespace App\Domain\Platform\Application\DTO;

final readonly class HariLiburData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $LokasiId = null,
        public mixed $Tanggal = null,
        public mixed $Nama = null,
        public mixed $BerulangTahunan = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            LokasiId: $data['LokasiId'] ?? null,
            Tanggal: $data['Tanggal'] ?? null,
            Nama: $data['Nama'] ?? null,
            BerulangTahunan: $data['BerulangTahunan'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
