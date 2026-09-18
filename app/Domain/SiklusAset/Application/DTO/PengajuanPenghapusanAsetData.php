<?php

declare(strict_types=1);

namespace App\Domain\SiklusAset\Application\DTO;

final readonly class PengajuanPenghapusanAsetData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $Nomor = null,
        public mixed $Alasan = null,
        public mixed $MetodePenghapusan = null,
        public mixed $Status = null,
        public mixed $DiajukanOleh = null,
        public mixed $DiajukanPada = null,
        public mixed $DiselesaikanPada = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            Nomor: $data['Nomor'] ?? null,
            Alasan: $data['Alasan'] ?? null,
            MetodePenghapusan: $data['MetodePenghapusan'] ?? null,
            Status: $data['Status'] ?? null,
            DiajukanOleh: $data['DiajukanOleh'] ?? null,
            DiajukanPada: $data['DiajukanPada'] ?? null,
            DiselesaikanPada: $data['DiselesaikanPada'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
