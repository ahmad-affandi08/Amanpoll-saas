<?php

declare(strict_types=1);

namespace App\Domain\Platform\Application\DTO;

final readonly class PenggunaPeranData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $PenggunaId = null,
        public mixed $PeranId = null,
        public mixed $UnitOrganisasiId = null,
        public mixed $LokasiId = null,
        public mixed $BerlakuMulai = null,
        public mixed $BerlakuSampai = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            PenggunaId: $data['PenggunaId'] ?? null,
            PeranId: $data['PeranId'] ?? null,
            UnitOrganisasiId: $data['UnitOrganisasiId'] ?? null,
            LokasiId: $data['LokasiId'] ?? null,
            BerlakuMulai: $data['BerlakuMulai'] ?? null,
            BerlakuSampai: $data['BerlakuSampai'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
