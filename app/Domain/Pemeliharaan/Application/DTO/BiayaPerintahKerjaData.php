<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Application\DTO;

final readonly class BiayaPerintahKerjaData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $PerintahKerjaId = null,
        public mixed $JenisBiaya = null,
        public mixed $Deskripsi = null,
        public mixed $Jumlah = null,
        public mixed $MataUang = null,
        public mixed $PenyediaId = null,
        public mixed $TanggalBiaya = null,
        public mixed $DibuatOleh = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            PerintahKerjaId: $data['PerintahKerjaId'] ?? null,
            JenisBiaya: $data['JenisBiaya'] ?? null,
            Deskripsi: $data['Deskripsi'] ?? null,
            Jumlah: $data['Jumlah'] ?? null,
            MataUang: $data['MataUang'] ?? null,
            PenyediaId: $data['PenyediaId'] ?? null,
            TanggalBiaya: $data['TanggalBiaya'] ?? null,
            DibuatOleh: $data['DibuatOleh'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
