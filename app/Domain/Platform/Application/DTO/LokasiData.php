<?php

declare(strict_types=1);

namespace App\Domain\Platform\Application\DTO;

final readonly class LokasiData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $UnitOrganisasiId = null,
        public mixed $KategoriLokasiId = null,
        public mixed $IndukId = null,
        public mixed $Kode = null,
        public mixed $Nama = null,
        public mixed $Alamat = null,
        public mixed $Lantai = null,
        public mixed $Latitude = null,
        public mixed $Longitude = null,
        public mixed $ZonaWaktu = null,
        public mixed $Status = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            UnitOrganisasiId: $data['UnitOrganisasiId'] ?? null,
            KategoriLokasiId: $data['KategoriLokasiId'] ?? null,
            IndukId: $data['IndukId'] ?? null,
            Kode: $data['Kode'] ?? null,
            Nama: $data['Nama'] ?? null,
            Alamat: $data['Alamat'] ?? null,
            Lantai: $data['Lantai'] ?? null,
            Latitude: $data['Latitude'] ?? null,
            Longitude: $data['Longitude'] ?? null,
            ZonaWaktu: $data['ZonaWaktu'] ?? null,
            Status: $data['Status'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
