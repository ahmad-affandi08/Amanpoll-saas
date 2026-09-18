<?php

declare(strict_types=1);

namespace App\Domain\Aset\Application\DTO;

final readonly class KategoriAsetData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $IndukId = null,
        public mixed $Kode = null,
        public mixed $Nama = null,
        public mixed $UmurManfaatBulan = null,
        public mixed $MetodePenyusutanBawaan = null,
        public mixed $PersentaseNilaiResidu = null,
        public mixed $MemerlukanKalibrasi = null,
        public mixed $MemerlukanPemeliharaan = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            IndukId: $data['IndukId'] ?? null,
            Kode: $data['Kode'] ?? null,
            Nama: $data['Nama'] ?? null,
            UmurManfaatBulan: $data['UmurManfaatBulan'] ?? null,
            MetodePenyusutanBawaan: $data['MetodePenyusutanBawaan'] ?? null,
            PersentaseNilaiResidu: $data['PersentaseNilaiResidu'] ?? null,
            MemerlukanKalibrasi: $data['MemerlukanKalibrasi'] ?? null,
            MemerlukanPemeliharaan: $data['MemerlukanPemeliharaan'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
