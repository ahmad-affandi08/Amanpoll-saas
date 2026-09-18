<?php

declare(strict_types=1);

namespace App\Domain\Aset\Application\DTO;

final readonly class PembacaanMeterAsetData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $MeterAsetId = null,
        public mixed $Nilai = null,
        public mixed $DibacaPada = null,
        public mixed $Sumber = null,
        public mixed $DicatatOleh = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            MeterAsetId: $data['MeterAsetId'] ?? null,
            Nilai: $data['Nilai'] ?? null,
            DibacaPada: $data['DibacaPada'] ?? null,
            Sumber: $data['Sumber'] ?? null,
            DicatatOleh: $data['DicatatOleh'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
