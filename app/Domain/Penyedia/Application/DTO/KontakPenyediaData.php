<?php

declare(strict_types=1);

namespace App\Domain\Penyedia\Application\DTO;

final readonly class KontakPenyediaData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $PenyediaId = null,
        public mixed $Nama = null,
        public mixed $Jabatan = null,
        public mixed $Email = null,
        public mixed $Telepon = null,
        public mixed $Utama = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            PenyediaId: $data['PenyediaId'] ?? null,
            Nama: $data['Nama'] ?? null,
            Jabatan: $data['Jabatan'] ?? null,
            Email: $data['Email'] ?? null,
            Telepon: $data['Telepon'] ?? null,
            Utama: $data['Utama'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
