<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Application\DTO;

final readonly class PembayaranPenyediaData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $TagihanPenyediaId = null,
        public mixed $NomorPembayaran = null,
        public mixed $TanggalBayar = null,
        public mixed $Jumlah = null,
        public mixed $Metode = null,
        public mixed $Referensi = null,
        public mixed $DibuatOleh = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            TagihanPenyediaId: $data['TagihanPenyediaId'] ?? null,
            NomorPembayaran: $data['NomorPembayaran'] ?? null,
            TanggalBayar: $data['TanggalBayar'] ?? null,
            Jumlah: $data['Jumlah'] ?? null,
            Metode: $data['Metode'] ?? null,
            Referensi: $data['Referensi'] ?? null,
            DibuatOleh: $data['DibuatOleh'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
