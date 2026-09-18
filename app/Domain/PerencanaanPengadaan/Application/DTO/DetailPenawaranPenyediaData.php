<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Application\DTO;

final readonly class DetailPenawaranPenyediaData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $PenawaranPenyediaId = null,
        public mixed $DetailPermintaanPembelianId = null,
        public mixed $Deskripsi = null,
        public mixed $Jumlah = null,
        public mixed $HargaSatuan = null,
        public mixed $Diskon = null,
        public mixed $Pajak = null,
        public mixed $Total = null,
        public mixed $WaktuPengirimanHari = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            PenawaranPenyediaId: $data['PenawaranPenyediaId'] ?? null,
            DetailPermintaanPembelianId: $data['DetailPermintaanPembelianId'] ?? null,
            Deskripsi: $data['Deskripsi'] ?? null,
            Jumlah: $data['Jumlah'] ?? null,
            HargaSatuan: $data['HargaSatuan'] ?? null,
            Diskon: $data['Diskon'] ?? null,
            Pajak: $data['Pajak'] ?? null,
            Total: $data['Total'] ?? null,
            WaktuPengirimanHari: $data['WaktuPengirimanHari'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
