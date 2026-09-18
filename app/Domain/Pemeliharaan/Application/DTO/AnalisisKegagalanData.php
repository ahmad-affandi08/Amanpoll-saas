<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Application\DTO;

final readonly class AnalisisKegagalanData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $PerintahKerjaId = null,
        public mixed $KodeMasalahId = null,
        public mixed $KodePenyebabId = null,
        public mixed $KodeTindakanId = null,
        public mixed $AkarMasalah = null,
        public mixed $TindakanKorektif = null,
        public mixed $TindakanPencegahan = null,
        public mixed $DibuatOleh = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            PerintahKerjaId: $data['PerintahKerjaId'] ?? null,
            KodeMasalahId: $data['KodeMasalahId'] ?? null,
            KodePenyebabId: $data['KodePenyebabId'] ?? null,
            KodeTindakanId: $data['KodeTindakanId'] ?? null,
            AkarMasalah: $data['AkarMasalah'] ?? null,
            TindakanKorektif: $data['TindakanKorektif'] ?? null,
            TindakanPencegahan: $data['TindakanPencegahan'] ?? null,
            DibuatOleh: $data['DibuatOleh'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
