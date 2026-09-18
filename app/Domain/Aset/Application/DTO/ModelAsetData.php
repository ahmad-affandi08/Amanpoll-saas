<?php

declare(strict_types=1);

namespace App\Domain\Aset\Application\DTO;

final readonly class ModelAsetData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $KategoriAsetId = null,
        public mixed $MerekId = null,
        public mixed $KodeModel = null,
        public mixed $Nama = null,
        public mixed $Produsen = null,
        public mixed $Spesifikasi = null,
        public mixed $IntervalPemeliharaanHari = null,
        public mixed $IntervalKalibrasiHari = null,
        public mixed $UmurManfaatBulan = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            KategoriAsetId: $data['KategoriAsetId'] ?? null,
            MerekId: $data['MerekId'] ?? null,
            KodeModel: $data['KodeModel'] ?? null,
            Nama: $data['Nama'] ?? null,
            Produsen: $data['Produsen'] ?? null,
            Spesifikasi: $data['Spesifikasi'] ?? null,
            IntervalPemeliharaanHari: $data['IntervalPemeliharaanHari'] ?? null,
            IntervalKalibrasiHari: $data['IntervalKalibrasiHari'] ?? null,
            UmurManfaatBulan: $data['UmurManfaatBulan'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
