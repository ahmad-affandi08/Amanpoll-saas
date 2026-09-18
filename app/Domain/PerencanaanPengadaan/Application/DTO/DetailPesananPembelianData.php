<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Application\DTO;

final readonly class DetailPesananPembelianData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $PesananPembelianId = null,
        public mixed $JenisItem = null,
        public mixed $SukuCadangId = null,
        public mixed $Deskripsi = null,
        public mixed $Jumlah = null,
        public mixed $Satuan = null,
        public mixed $HargaSatuan = null,
        public mixed $Diskon = null,
        public mixed $Pajak = null,
        public mixed $Total = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            PesananPembelianId: $data['PesananPembelianId'] ?? null,
            JenisItem: $data['JenisItem'] ?? null,
            SukuCadangId: $data['SukuCadangId'] ?? null,
            Deskripsi: $data['Deskripsi'] ?? null,
            Jumlah: $data['Jumlah'] ?? null,
            Satuan: $data['Satuan'] ?? null,
            HargaSatuan: $data['HargaSatuan'] ?? null,
            Diskon: $data['Diskon'] ?? null,
            Pajak: $data['Pajak'] ?? null,
            Total: $data['Total'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
