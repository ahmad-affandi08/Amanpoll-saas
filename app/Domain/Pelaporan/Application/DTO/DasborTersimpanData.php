<?php

declare(strict_types=1);

namespace App\Domain\Pelaporan\Application\DTO;

final readonly class DasborTersimpanData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $Nama = null,
        public mixed $PemilikId = null,
        public mixed $Bawaan = null,
        public mixed $Konfigurasi = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            Nama: $data['Nama'] ?? null,
            PemilikId: $data['PemilikId'] ?? null,
            Bawaan: $data['Bawaan'] ?? null,
            Konfigurasi: $data['Konfigurasi'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
