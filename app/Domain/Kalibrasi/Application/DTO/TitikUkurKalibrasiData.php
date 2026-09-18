<?php

declare(strict_types=1);

namespace App\Domain\Kalibrasi\Application\DTO;

final readonly class TitikUkurKalibrasiData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $JenisKalibrasiId = null,
        public mixed $KategoriAsetId = null,
        public mixed $Nama = null,
        public mixed $Satuan = null,
        public mixed $NilaiReferensi = null,
        public mixed $ToleransiMinus = null,
        public mixed $ToleransiPlus = null,
        public mixed $Urutan = null,
        public mixed $Aktif = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            JenisKalibrasiId: $data['JenisKalibrasiId'] ?? null,
            KategoriAsetId: $data['KategoriAsetId'] ?? null,
            Nama: $data['Nama'] ?? null,
            Satuan: $data['Satuan'] ?? null,
            NilaiReferensi: $data['NilaiReferensi'] ?? null,
            ToleransiMinus: $data['ToleransiMinus'] ?? null,
            ToleransiPlus: $data['ToleransiPlus'] ?? null,
            Urutan: $data['Urutan'] ?? null,
            Aktif: $data['Aktif'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
