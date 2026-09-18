<?php

declare(strict_types=1);

namespace App\Domain\SiklusAset\Application\DTO;

final readonly class DetailPenghapusanAsetData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $PengajuanPenghapusanAsetId = null,
        public mixed $AsetId = null,
        public mixed $NilaiBukuSaatPenghapusan = null,
        public mixed $HasilPelepasan = null,
        public mixed $Status = null,
        public mixed $Catatan = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            PengajuanPenghapusanAsetId: $data['PengajuanPenghapusanAsetId'] ?? null,
            AsetId: $data['AsetId'] ?? null,
            NilaiBukuSaatPenghapusan: $data['NilaiBukuSaatPenghapusan'] ?? null,
            HasilPelepasan: $data['HasilPelepasan'] ?? null,
            Status: $data['Status'] ?? null,
            Catatan: $data['Catatan'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
