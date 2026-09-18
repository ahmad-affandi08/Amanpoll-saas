<?php

declare(strict_types=1);

namespace App\Domain\Platform\Application\DTO;

final readonly class KunciApiData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $Nama = null,
        public mixed $AwalanKunci = null,
        public mixed $HashKunci = null,
        public mixed $Cakupan = null,
        public mixed $AlamatIpDiizinkan = null,
        public mixed $KadaluarsaPada = null,
        public mixed $TerakhirDipakaiPada = null,
        public mixed $Status = null,
        public mixed $DibuatOleh = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            Nama: $data['Nama'] ?? null,
            AwalanKunci: $data['AwalanKunci'] ?? null,
            HashKunci: $data['HashKunci'] ?? null,
            Cakupan: $data['Cakupan'] ?? null,
            AlamatIpDiizinkan: $data['AlamatIpDiizinkan'] ?? null,
            KadaluarsaPada: $data['KadaluarsaPada'] ?? null,
            TerakhirDipakaiPada: $data['TerakhirDipakaiPada'] ?? null,
            Status: $data['Status'] ?? null,
            DibuatOleh: $data['DibuatOleh'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
