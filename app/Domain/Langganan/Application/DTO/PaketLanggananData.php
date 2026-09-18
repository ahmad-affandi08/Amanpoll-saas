<?php

declare(strict_types=1);

namespace App\Domain\Langganan\Application\DTO;

final readonly class PaketLanggananData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $Kode = null,
        public mixed $Nama = null,
        public mixed $Deskripsi = null,
        public mixed $HargaBulanan = null,
        public mixed $HargaTahunan = null,
        public mixed $MataUang = null,
        public mixed $Aktif = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            Kode: $data['Kode'] ?? null,
            Nama: $data['Nama'] ?? null,
            Deskripsi: $data['Deskripsi'] ?? null,
            HargaBulanan: $data['HargaBulanan'] ?? null,
            HargaTahunan: $data['HargaTahunan'] ?? null,
            MataUang: $data['MataUang'] ?? null,
            Aktif: $data['Aktif'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
