<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Application\DTO;

final readonly class PenerimaanPembelianData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $Nomor = null,
        public mixed $PesananPembelianId = null,
        public mixed $GudangId = null,
        public mixed $TanggalTerima = null,
        public mixed $NomorSuratJalan = null,
        public mixed $DiterimaOleh = null,
        public mixed $Status = null,
        public mixed $Catatan = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            Nomor: $data['Nomor'] ?? null,
            PesananPembelianId: $data['PesananPembelianId'] ?? null,
            GudangId: $data['GudangId'] ?? null,
            TanggalTerima: $data['TanggalTerima'] ?? null,
            NomorSuratJalan: $data['NomorSuratJalan'] ?? null,
            DiterimaOleh: $data['DiterimaOleh'] ?? null,
            Status: $data['Status'] ?? null,
            Catatan: $data['Catatan'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
