<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Application\DTO;

final readonly class KeluhanData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $Nomor = null,
        public mixed $KategoriKeluhanId = null,
        public mixed $AsetId = null,
        public mixed $LokasiId = null,
        public mixed $Judul = null,
        public mixed $Deskripsi = null,
        public mixed $Prioritas = null,
        public mixed $Status = null,
        public mixed $Sumber = null,
        public mixed $PelaporId = null,
        public mixed $NamaPelaporEksternal = null,
        public mixed $KontakPelaporEksternal = null,
        public mixed $DilaporkanPada = null,
        public mixed $DiresponsPada = null,
        public mixed $DitutupPada = null,
        public mixed $Rating = null,
        public mixed $Ulasan = null,
        public mixed $Versi = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            Nomor: $data['Nomor'] ?? null,
            KategoriKeluhanId: $data['KategoriKeluhanId'] ?? null,
            AsetId: $data['AsetId'] ?? null,
            LokasiId: $data['LokasiId'] ?? null,
            Judul: $data['Judul'] ?? null,
            Deskripsi: $data['Deskripsi'] ?? null,
            Prioritas: $data['Prioritas'] ?? null,
            Status: $data['Status'] ?? null,
            Sumber: $data['Sumber'] ?? null,
            PelaporId: $data['PelaporId'] ?? null,
            NamaPelaporEksternal: $data['NamaPelaporEksternal'] ?? null,
            KontakPelaporEksternal: $data['KontakPelaporEksternal'] ?? null,
            DilaporkanPada: $data['DilaporkanPada'] ?? null,
            DiresponsPada: $data['DiresponsPada'] ?? null,
            DitutupPada: $data['DitutupPada'] ?? null,
            Rating: $data['Rating'] ?? null,
            Ulasan: $data['Ulasan'] ?? null,
            Versi: $data['Versi'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
