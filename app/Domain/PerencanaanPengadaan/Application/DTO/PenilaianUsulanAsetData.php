<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Application\DTO;

final readonly class PenilaianUsulanAsetData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $UsulanAsetId = null,
        public mixed $Kriteria = null,
        public mixed $Bobot = null,
        public mixed $Nilai = null,
        public mixed $Skor = null,
        public mixed $DinilaiOleh = null,
        public mixed $DinilaiPada = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            UsulanAsetId: $data['UsulanAsetId'] ?? null,
            Kriteria: $data['Kriteria'] ?? null,
            Bobot: $data['Bobot'] ?? null,
            Nilai: $data['Nilai'] ?? null,
            Skor: $data['Skor'] ?? null,
            DinilaiOleh: $data['DinilaiOleh'] ?? null,
            DinilaiPada: $data['DinilaiPada'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
