<?php

declare(strict_types=1);

namespace App\Domain\Kepatuhan\Application\DTO;

final readonly class PersyaratanKepatuhanData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $StandarKepatuhanId = null,
        public mixed $Kode = null,
        public mixed $Nama = null,
        public mixed $Deskripsi = null,
        public mixed $BuktiYangDiperlukan = null,
        public mixed $IntervalHari = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            StandarKepatuhanId: $data['StandarKepatuhanId'] ?? null,
            Kode: $data['Kode'] ?? null,
            Nama: $data['Nama'] ?? null,
            Deskripsi: $data['Deskripsi'] ?? null,
            BuktiYangDiperlukan: $data['BuktiYangDiperlukan'] ?? null,
            IntervalHari: $data['IntervalHari'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
