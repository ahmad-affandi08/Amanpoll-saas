<?php

declare(strict_types=1);

namespace App\Domain\Kepatuhan\Application\DTO;

final readonly class StandarKepatuhanData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $Kode = null,
        public mixed $Nama = null,
        public mixed $Penerbit = null,
        public mixed $VersiStandar = null,
        public mixed $JenisIndustri = null,
        public mixed $Deskripsi = null,
        public mixed $Aktif = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            Kode: $data['Kode'] ?? null,
            Nama: $data['Nama'] ?? null,
            Penerbit: $data['Penerbit'] ?? null,
            VersiStandar: $data['VersiStandar'] ?? null,
            JenisIndustri: $data['JenisIndustri'] ?? null,
            Deskripsi: $data['Deskripsi'] ?? null,
            Aktif: $data['Aktif'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
