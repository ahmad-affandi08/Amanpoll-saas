<?php

declare(strict_types=1);

namespace App\Domain\Aset\Application\DTO;

final readonly class GaransiAsetData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $AsetId = null,
        public mixed $PenyediaId = null,
        public mixed $NomorGaransi = null,
        public mixed $JenisGaransi = null,
        public mixed $MulaiPada = null,
        public mixed $BerakhirPada = null,
        public mixed $Cakupan = null,
        public mixed $Status = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            AsetId: $data['AsetId'] ?? null,
            PenyediaId: $data['PenyediaId'] ?? null,
            NomorGaransi: $data['NomorGaransi'] ?? null,
            JenisGaransi: $data['JenisGaransi'] ?? null,
            MulaiPada: $data['MulaiPada'] ?? null,
            BerakhirPada: $data['BerakhirPada'] ?? null,
            Cakupan: $data['Cakupan'] ?? null,
            Status: $data['Status'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
