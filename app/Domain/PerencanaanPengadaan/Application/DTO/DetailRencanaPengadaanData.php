<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Application\DTO;

final readonly class DetailRencanaPengadaanData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $RencanaPengadaanId = null,
        public mixed $UsulanAsetId = null,
        public mixed $SukuCadangId = null,
        public mixed $Deskripsi = null,
        public mixed $Jumlah = null,
        public mixed $Satuan = null,
        public mixed $HargaEstimasi = null,
        public mixed $BulanRencana = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            RencanaPengadaanId: $data['RencanaPengadaanId'] ?? null,
            UsulanAsetId: $data['UsulanAsetId'] ?? null,
            SukuCadangId: $data['SukuCadangId'] ?? null,
            Deskripsi: $data['Deskripsi'] ?? null,
            Jumlah: $data['Jumlah'] ?? null,
            Satuan: $data['Satuan'] ?? null,
            HargaEstimasi: $data['HargaEstimasi'] ?? null,
            BulanRencana: $data['BulanRencana'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
