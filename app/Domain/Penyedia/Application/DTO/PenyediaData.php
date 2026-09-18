<?php

declare(strict_types=1);

namespace App\Domain\Penyedia\Application\DTO;

final readonly class PenyediaData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $Kode = null,
        public mixed $Nama = null,
        public mixed $NamaLegal = null,
        public mixed $NomorIdentitasPajak = null,
        public mixed $Email = null,
        public mixed $Telepon = null,
        public mixed $Website = null,
        public mixed $Alamat = null,
        public mixed $Kota = null,
        public mixed $Provinsi = null,
        public mixed $Negara = null,
        public mixed $Status = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            Kode: $data['Kode'] ?? null,
            Nama: $data['Nama'] ?? null,
            NamaLegal: $data['NamaLegal'] ?? null,
            NomorIdentitasPajak: $data['NomorIdentitasPajak'] ?? null,
            Email: $data['Email'] ?? null,
            Telepon: $data['Telepon'] ?? null,
            Website: $data['Website'] ?? null,
            Alamat: $data['Alamat'] ?? null,
            Kota: $data['Kota'] ?? null,
            Provinsi: $data['Provinsi'] ?? null,
            Negara: $data['Negara'] ?? null,
            Status: $data['Status'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
