<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Application\DTO;

final readonly class UsulanAsetData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $Nomor = null,
        public mixed $UnitOrganisasiId = null,
        public mixed $KategoriAsetId = null,
        public mixed $ModelAsetId = null,
        public mixed $NamaKebutuhan = null,
        public mixed $Jumlah = null,
        public mixed $EstimasiHargaSatuan = null,
        public mixed $Alasan = null,
        public mixed $JenisKebutuhan = null,
        public mixed $TahunKebutuhan = null,
        public mixed $Prioritas = null,
        public mixed $Status = null,
        public mixed $DiajukanOleh = null,
        public mixed $DiajukanPada = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            Nomor: $data['Nomor'] ?? null,
            UnitOrganisasiId: $data['UnitOrganisasiId'] ?? null,
            KategoriAsetId: $data['KategoriAsetId'] ?? null,
            ModelAsetId: $data['ModelAsetId'] ?? null,
            NamaKebutuhan: $data['NamaKebutuhan'] ?? null,
            Jumlah: $data['Jumlah'] ?? null,
            EstimasiHargaSatuan: $data['EstimasiHargaSatuan'] ?? null,
            Alasan: $data['Alasan'] ?? null,
            JenisKebutuhan: $data['JenisKebutuhan'] ?? null,
            TahunKebutuhan: $data['TahunKebutuhan'] ?? null,
            Prioritas: $data['Prioritas'] ?? null,
            Status: $data['Status'] ?? null,
            DiajukanOleh: $data['DiajukanOleh'] ?? null,
            DiajukanPada: $data['DiajukanPada'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
