<?php

declare(strict_types=1);

namespace App\Domain\Pelaporan\Application\DTO;

final readonly class KomponenDasborData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $DasborTersimpanId = null,
        public mixed $JenisKomponen = null,
        public mixed $Judul = null,
        public mixed $Konfigurasi = null,
        public mixed $PosisiX = null,
        public mixed $PosisiY = null,
        public mixed $Lebar = null,
        public mixed $Tinggi = null,
        public mixed $Urutan = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            DasborTersimpanId: $data['DasborTersimpanId'] ?? null,
            JenisKomponen: $data['JenisKomponen'] ?? null,
            Judul: $data['Judul'] ?? null,
            Konfigurasi: $data['Konfigurasi'] ?? null,
            PosisiX: $data['PosisiX'] ?? null,
            PosisiY: $data['PosisiY'] ?? null,
            Lebar: $data['Lebar'] ?? null,
            Tinggi: $data['Tinggi'] ?? null,
            Urutan: $data['Urutan'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
