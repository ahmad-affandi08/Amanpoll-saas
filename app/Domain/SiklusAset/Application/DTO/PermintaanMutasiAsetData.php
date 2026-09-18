<?php

declare(strict_types=1);

namespace App\Domain\SiklusAset\Application\DTO;

final readonly class PermintaanMutasiAsetData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $Nomor = null,
        public mixed $JenisMutasi = null,
        public mixed $UnitAsalId = null,
        public mixed $UnitTujuanId = null,
        public mixed $LokasiAsalId = null,
        public mixed $LokasiTujuanId = null,
        public mixed $Alasan = null,
        public mixed $Status = null,
        public mixed $DimintaOleh = null,
        public mixed $DimintaPada = null,
        public mixed $DisetujuiPada = null,
        public mixed $SelesaiPada = null,
        public mixed $Versi = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            Nomor: $data['Nomor'] ?? null,
            JenisMutasi: $data['JenisMutasi'] ?? null,
            UnitAsalId: $data['UnitAsalId'] ?? null,
            UnitTujuanId: $data['UnitTujuanId'] ?? null,
            LokasiAsalId: $data['LokasiAsalId'] ?? null,
            LokasiTujuanId: $data['LokasiTujuanId'] ?? null,
            Alasan: $data['Alasan'] ?? null,
            Status: $data['Status'] ?? null,
            DimintaOleh: $data['DimintaOleh'] ?? null,
            DimintaPada: $data['DimintaPada'] ?? null,
            DisetujuiPada: $data['DisetujuiPada'] ?? null,
            SelesaiPada: $data['SelesaiPada'] ?? null,
            Versi: $data['Versi'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
