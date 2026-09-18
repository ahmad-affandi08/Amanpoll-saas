<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Application\DTO;

final readonly class DetailPermintaanPembelianData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $PermintaanPembelianId = null,
        public mixed $JenisItem = null,
        public mixed $AsetReferensiId = null,
        public mixed $SukuCadangId = null,
        public mixed $Deskripsi = null,
        public mixed $Jumlah = null,
        public mixed $Satuan = null,
        public mixed $HargaEstimasi = null,
        public mixed $Spesifikasi = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            PermintaanPembelianId: $data['PermintaanPembelianId'] ?? null,
            JenisItem: $data['JenisItem'] ?? null,
            AsetReferensiId: $data['AsetReferensiId'] ?? null,
            SukuCadangId: $data['SukuCadangId'] ?? null,
            Deskripsi: $data['Deskripsi'] ?? null,
            Jumlah: $data['Jumlah'] ?? null,
            Satuan: $data['Satuan'] ?? null,
            HargaEstimasi: $data['HargaEstimasi'] ?? null,
            Spesifikasi: $data['Spesifikasi'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
