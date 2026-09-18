<?php

declare(strict_types=1);

namespace App\Domain\IntegrasiAudit\Application\DTO;

final readonly class KotakKeluarPeristiwaData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $NamaPeristiwa = null,
        public mixed $JenisAgregat = null,
        public mixed $AgregatId = null,
        public mixed $MuatanData = null,
        public mixed $Status = null,
        public mixed $Percobaan = null,
        public mixed $TersediaPada = null,
        public mixed $DiprosesPada = null,
        public mixed $KesalahanTerakhir = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            NamaPeristiwa: $data['NamaPeristiwa'] ?? null,
            JenisAgregat: $data['JenisAgregat'] ?? null,
            AgregatId: $data['AgregatId'] ?? null,
            MuatanData: $data['MuatanData'] ?? null,
            Status: $data['Status'] ?? null,
            Percobaan: $data['Percobaan'] ?? null,
            TersediaPada: $data['TersediaPada'] ?? null,
            DiprosesPada: $data['DiprosesPada'] ?? null,
            KesalahanTerakhir: $data['KesalahanTerakhir'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
