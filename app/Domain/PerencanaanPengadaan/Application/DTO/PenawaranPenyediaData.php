<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Application\DTO;

final readonly class PenawaranPenyediaData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $PermintaanPenawaranId = null,
        public mixed $PenyediaId = null,
        public mixed $NomorPenawaran = null,
        public mixed $TanggalPenawaran = null,
        public mixed $BerlakuSampai = null,
        public mixed $MataUang = null,
        public mixed $Subtotal = null,
        public mixed $Pajak = null,
        public mixed $Diskon = null,
        public mixed $Total = null,
        public mixed $Status = null,
        public mixed $Catatan = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            PermintaanPenawaranId: $data['PermintaanPenawaranId'] ?? null,
            PenyediaId: $data['PenyediaId'] ?? null,
            NomorPenawaran: $data['NomorPenawaran'] ?? null,
            TanggalPenawaran: $data['TanggalPenawaran'] ?? null,
            BerlakuSampai: $data['BerlakuSampai'] ?? null,
            MataUang: $data['MataUang'] ?? null,
            Subtotal: $data['Subtotal'] ?? null,
            Pajak: $data['Pajak'] ?? null,
            Diskon: $data['Diskon'] ?? null,
            Total: $data['Total'] ?? null,
            Status: $data['Status'] ?? null,
            Catatan: $data['Catatan'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
