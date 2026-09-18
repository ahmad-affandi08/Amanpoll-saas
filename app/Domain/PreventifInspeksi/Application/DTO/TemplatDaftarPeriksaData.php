<?php

declare(strict_types=1);

namespace App\Domain\PreventifInspeksi\Application\DTO;

final readonly class TemplatDaftarPeriksaData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $Kode = null,
        public mixed $Nama = null,
        public mixed $Jenis = null,
        public mixed $KategoriAsetId = null,
        public mixed $ModelAsetId = null,
        public mixed $VersiTemplat = null,
        public mixed $Aktif = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            Kode: $data['Kode'] ?? null,
            Nama: $data['Nama'] ?? null,
            Jenis: $data['Jenis'] ?? null,
            KategoriAsetId: $data['KategoriAsetId'] ?? null,
            ModelAsetId: $data['ModelAsetId'] ?? null,
            VersiTemplat: $data['VersiTemplat'] ?? null,
            Aktif: $data['Aktif'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
