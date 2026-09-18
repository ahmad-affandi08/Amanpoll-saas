<?php

declare(strict_types=1);

namespace App\Domain\Kolaborasi\Application\DTO;

final readonly class BerkasData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $NamaAsli = null,
        public mixed $NamaPenyimpanan = null,
        public mixed $MediaPenyimpanan = null,
        public mixed $LokasiPenyimpanan = null,
        public mixed $JenisMime = null,
        public mixed $UkuranByte = null,
        public mixed $HashSha256 = null,
        public mixed $DataTambahan = null,
        public mixed $DiunggahOleh = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            NamaAsli: $data['NamaAsli'] ?? null,
            NamaPenyimpanan: $data['NamaPenyimpanan'] ?? null,
            MediaPenyimpanan: $data['MediaPenyimpanan'] ?? null,
            LokasiPenyimpanan: $data['LokasiPenyimpanan'] ?? null,
            JenisMime: $data['JenisMime'] ?? null,
            UkuranByte: $data['UkuranByte'] ?? null,
            HashSha256: $data['HashSha256'] ?? null,
            DataTambahan: $data['DataTambahan'] ?? null,
            DiunggahOleh: $data['DiunggahOleh'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
