<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Application\DTO;

final readonly class AnggaranData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $UnitOrganisasiId = null,
        public mixed $Kode = null,
        public mixed $Nama = null,
        public mixed $Tahun = null,
        public mixed $MataUang = null,
        public mixed $Jumlah = null,
        public mixed $Status = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            UnitOrganisasiId: $data['UnitOrganisasiId'] ?? null,
            Kode: $data['Kode'] ?? null,
            Nama: $data['Nama'] ?? null,
            Tahun: $data['Tahun'] ?? null,
            MataUang: $data['MataUang'] ?? null,
            Jumlah: $data['Jumlah'] ?? null,
            Status: $data['Status'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
