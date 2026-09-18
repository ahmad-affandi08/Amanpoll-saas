<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Application\DTO;

final readonly class PermintaanPembelianData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $Nomor = null,
        public mixed $UnitOrganisasiId = null,
        public mixed $RencanaPengadaanId = null,
        public mixed $PosAnggaranId = null,
        public mixed $TanggalPermintaan = null,
        public mixed $TanggalDibutuhkan = null,
        public mixed $Prioritas = null,
        public mixed $Status = null,
        public mixed $Alasan = null,
        public mixed $DimintaOleh = null,
        public mixed $TotalEstimasi = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            Nomor: $data['Nomor'] ?? null,
            UnitOrganisasiId: $data['UnitOrganisasiId'] ?? null,
            RencanaPengadaanId: $data['RencanaPengadaanId'] ?? null,
            PosAnggaranId: $data['PosAnggaranId'] ?? null,
            TanggalPermintaan: $data['TanggalPermintaan'] ?? null,
            TanggalDibutuhkan: $data['TanggalDibutuhkan'] ?? null,
            Prioritas: $data['Prioritas'] ?? null,
            Status: $data['Status'] ?? null,
            Alasan: $data['Alasan'] ?? null,
            DimintaOleh: $data['DimintaOleh'] ?? null,
            TotalEstimasi: $data['TotalEstimasi'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
