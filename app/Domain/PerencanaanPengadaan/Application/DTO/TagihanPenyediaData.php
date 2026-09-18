<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Application\DTO;

final readonly class TagihanPenyediaData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $PenyediaId = null,
        public mixed $PesananPembelianId = null,
        public mixed $NomorTagihan = null,
        public mixed $TanggalTagihan = null,
        public mixed $JatuhTempo = null,
        public mixed $Subtotal = null,
        public mixed $Pajak = null,
        public mixed $Total = null,
        public mixed $Sisa = null,
        public mixed $Status = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            PenyediaId: $data['PenyediaId'] ?? null,
            PesananPembelianId: $data['PesananPembelianId'] ?? null,
            NomorTagihan: $data['NomorTagihan'] ?? null,
            TanggalTagihan: $data['TanggalTagihan'] ?? null,
            JatuhTempo: $data['JatuhTempo'] ?? null,
            Subtotal: $data['Subtotal'] ?? null,
            Pajak: $data['Pajak'] ?? null,
            Total: $data['Total'] ?? null,
            Sisa: $data['Sisa'] ?? null,
            Status: $data['Status'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
