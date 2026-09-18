<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Application\DTO;

final readonly class TransaksiAnggaranData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $PosAnggaranId = null,
        public mixed $Jenis = null,
        public mixed $ReferensiJenis = null,
        public mixed $ReferensiId = null,
        public mixed $Jumlah = null,
        public mixed $Tanggal = null,
        public mixed $Keterangan = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            PosAnggaranId: $data['PosAnggaranId'] ?? null,
            Jenis: $data['Jenis'] ?? null,
            ReferensiJenis: $data['ReferensiJenis'] ?? null,
            ReferensiId: $data['ReferensiId'] ?? null,
            Jumlah: $data['Jumlah'] ?? null,
            Tanggal: $data['Tanggal'] ?? null,
            Keterangan: $data['Keterangan'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
