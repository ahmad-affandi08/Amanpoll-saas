<?php

declare(strict_types=1);

namespace App\Domain\Platform\Application\DTO;

final readonly class OrganisasiData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $Kode = null,
        public mixed $Nama = null,
        public mixed $NamaLegal = null,
        public mixed $JenisUsaha = null,
        public mixed $NomorIdentitasPajak = null,
        public mixed $Email = null,
        public mixed $Telepon = null,
        public mixed $Alamat = null,
        public mixed $Negara = null,
        public mixed $Provinsi = null,
        public mixed $Kota = null,
        public mixed $ZonaWaktu = null,
        public mixed $LogoUrl = null,
        public mixed $Status = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            Kode: $data['Kode'] ?? null,
            Nama: $data['Nama'] ?? null,
            NamaLegal: $data['NamaLegal'] ?? null,
            JenisUsaha: $data['JenisUsaha'] ?? null,
            NomorIdentitasPajak: $data['NomorIdentitasPajak'] ?? null,
            Email: $data['Email'] ?? null,
            Telepon: $data['Telepon'] ?? null,
            Alamat: $data['Alamat'] ?? null,
            Negara: $data['Negara'] ?? null,
            Provinsi: $data['Provinsi'] ?? null,
            Kota: $data['Kota'] ?? null,
            ZonaWaktu: $data['ZonaWaktu'] ?? null,
            LogoUrl: $data['LogoUrl'] ?? null,
            Status: $data['Status'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
