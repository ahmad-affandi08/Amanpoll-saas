<?php

declare(strict_types=1);

namespace App\Domain\Aset\Application\DTO;

final readonly class NilaiAsetData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $AsetId = null,
        public mixed $TanggalNilai = null,
        public mixed $NilaiBuku = null,
        public mixed $AkumulasiPenyusutan = null,
        public mixed $BebanPenyusutanPeriode = null,
        public mixed $Metode = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            AsetId: $data['AsetId'] ?? null,
            TanggalNilai: $data['TanggalNilai'] ?? null,
            NilaiBuku: $data['NilaiBuku'] ?? null,
            AkumulasiPenyusutan: $data['AkumulasiPenyusutan'] ?? null,
            BebanPenyusutanPeriode: $data['BebanPenyusutanPeriode'] ?? null,
            Metode: $data['Metode'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
