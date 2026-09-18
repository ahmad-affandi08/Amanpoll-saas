<?php

declare(strict_types=1);

namespace App\Domain\Platform\Application\DTO;

final readonly class IzinData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $Kode = null,
        public mixed $Nama = null,
        public mixed $Modul = null,
        public mixed $Keterangan = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            Kode: $data['Kode'] ?? null,
            Nama: $data['Nama'] ?? null,
            Modul: $data['Modul'] ?? null,
            Keterangan: $data['Keterangan'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
