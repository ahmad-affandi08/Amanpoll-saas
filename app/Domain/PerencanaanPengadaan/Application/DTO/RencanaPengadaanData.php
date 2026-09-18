<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Application\DTO;

final readonly class RencanaPengadaanData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $Nomor = null,
        public mixed $Nama = null,
        public mixed $Tahun = null,
        public mixed $PosAnggaranId = null,
        public mixed $Status = null,
        public mixed $TotalEstimasi = null,
        public mixed $DibuatOleh = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            Nomor: $data['Nomor'] ?? null,
            Nama: $data['Nama'] ?? null,
            Tahun: $data['Tahun'] ?? null,
            PosAnggaranId: $data['PosAnggaranId'] ?? null,
            Status: $data['Status'] ?? null,
            TotalEstimasi: $data['TotalEstimasi'] ?? null,
            DibuatOleh: $data['DibuatOleh'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
