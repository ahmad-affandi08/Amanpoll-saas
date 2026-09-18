<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Application\DTO;

final readonly class SukuCadangData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $KategoriSukuCadangId = null,
        public mixed $Kode = null,
        public mixed $Nama = null,
        public mixed $NomorBagian = null,
        public mixed $KodeBatang = null,
        public mixed $SatuanDasar = null,
        public mixed $StokMinimum = null,
        public mixed $StokMaksimum = null,
        public mixed $TitikPesanUlang = null,
        public mixed $HargaRataRata = null,
        public mixed $MemakaiBatch = null,
        public mixed $MemakaiKadaluarsa = null,
        public mixed $Status = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            KategoriSukuCadangId: $data['KategoriSukuCadangId'] ?? null,
            Kode: $data['Kode'] ?? null,
            Nama: $data['Nama'] ?? null,
            NomorBagian: $data['NomorBagian'] ?? null,
            KodeBatang: $data['KodeBatang'] ?? null,
            SatuanDasar: $data['SatuanDasar'] ?? null,
            StokMinimum: $data['StokMinimum'] ?? null,
            StokMaksimum: $data['StokMaksimum'] ?? null,
            TitikPesanUlang: $data['TitikPesanUlang'] ?? null,
            HargaRataRata: $data['HargaRataRata'] ?? null,
            MemakaiBatch: $data['MemakaiBatch'] ?? null,
            MemakaiKadaluarsa: $data['MemakaiKadaluarsa'] ?? null,
            Status: $data['Status'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
