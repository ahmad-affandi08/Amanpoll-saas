<?php

declare(strict_types=1);

namespace App\Domain\Kepatuhan\Application\DTO;

final readonly class KepatuhanAsetData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $AsetId = null,
        public mixed $PersyaratanKepatuhanId = null,
        public mixed $Status = null,
        public mixed $TanggalPemeriksaan = null,
        public mixed $BerlakuSampai = null,
        public mixed $Catatan = null,
        public mixed $DiperiksaOleh = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            AsetId: $data['AsetId'] ?? null,
            PersyaratanKepatuhanId: $data['PersyaratanKepatuhanId'] ?? null,
            Status: $data['Status'] ?? null,
            TanggalPemeriksaan: $data['TanggalPemeriksaan'] ?? null,
            BerlakuSampai: $data['BerlakuSampai'] ?? null,
            Catatan: $data['Catatan'] ?? null,
            DiperiksaOleh: $data['DiperiksaOleh'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
