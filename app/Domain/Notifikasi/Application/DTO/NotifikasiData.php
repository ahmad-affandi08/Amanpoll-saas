<?php

declare(strict_types=1);

namespace App\Domain\Notifikasi\Application\DTO;

final readonly class NotifikasiData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $PenggunaId = null,
        public mixed $Kanal = null,
        public mixed $JenisPeristiwa = null,
        public mixed $Judul = null,
        public mixed $Isi = null,
        public mixed $JenisEntitas = null,
        public mixed $EntitasId = null,
        public mixed $Status = null,
        public mixed $JadwalKirimPada = null,
        public mixed $DikirimPada = null,
        public mixed $DibacaPada = null,
        public mixed $Percobaan = null,
        public mixed $KesalahanTerakhir = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            PenggunaId: $data['PenggunaId'] ?? null,
            Kanal: $data['Kanal'] ?? null,
            JenisPeristiwa: $data['JenisPeristiwa'] ?? null,
            Judul: $data['Judul'] ?? null,
            Isi: $data['Isi'] ?? null,
            JenisEntitas: $data['JenisEntitas'] ?? null,
            EntitasId: $data['EntitasId'] ?? null,
            Status: $data['Status'] ?? null,
            JadwalKirimPada: $data['JadwalKirimPada'] ?? null,
            DikirimPada: $data['DikirimPada'] ?? null,
            DibacaPada: $data['DibacaPada'] ?? null,
            Percobaan: $data['Percobaan'] ?? null,
            KesalahanTerakhir: $data['KesalahanTerakhir'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
