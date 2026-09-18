<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Application\DTO;

final readonly class PermintaanPenawaranData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $Nomor = null,
        public mixed $PermintaanPembelianId = null,
        public mixed $TanggalDibuka = null,
        public mixed $BatasPenawaran = null,
        public mixed $Status = null,
        public mixed $Catatan = null,
        public mixed $DibuatOleh = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            Nomor: $data['Nomor'] ?? null,
            PermintaanPembelianId: $data['PermintaanPembelianId'] ?? null,
            TanggalDibuka: $data['TanggalDibuka'] ?? null,
            BatasPenawaran: $data['BatasPenawaran'] ?? null,
            Status: $data['Status'] ?? null,
            Catatan: $data['Catatan'] ?? null,
            DibuatOleh: $data['DibuatOleh'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
