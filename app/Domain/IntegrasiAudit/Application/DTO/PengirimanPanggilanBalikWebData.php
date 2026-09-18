<?php

declare(strict_types=1);

namespace App\Domain\IntegrasiAudit\Application\DTO;

final readonly class PengirimanPanggilanBalikWebData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $PanggilanBalikWebId = null,
        public mixed $Peristiwa = null,
        public mixed $MuatanData = null,
        public mixed $StatusHttp = null,
        public mixed $Respons = null,
        public mixed $Status = null,
        public mixed $Percobaan = null,
        public mixed $JadwalCobaLagiPada = null,
        public mixed $DikirimPada = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            PanggilanBalikWebId: $data['PanggilanBalikWebId'] ?? null,
            Peristiwa: $data['Peristiwa'] ?? null,
            MuatanData: $data['MuatanData'] ?? null,
            StatusHttp: $data['StatusHttp'] ?? null,
            Respons: $data['Respons'] ?? null,
            Status: $data['Status'] ?? null,
            Percobaan: $data['Percobaan'] ?? null,
            JadwalCobaLagiPada: $data['JadwalCobaLagiPada'] ?? null,
            DikirimPada: $data['DikirimPada'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
