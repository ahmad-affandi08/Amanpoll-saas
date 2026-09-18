<?php

declare(strict_types=1);

namespace App\Domain\SiklusAset\Application\DTO;

final readonly class SerahTerimaAsetData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $Nomor = null,
        public mixed $PermintaanMutasiAsetId = null,
        public mixed $Jenis = null,
        public mixed $PihakMenyerahkan = null,
        public mixed $PihakMenerima = null,
        public mixed $DiserahkanPada = null,
        public mixed $DiterimaPada = null,
        public mixed $Status = null,
        public mixed $Catatan = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            Nomor: $data['Nomor'] ?? null,
            PermintaanMutasiAsetId: $data['PermintaanMutasiAsetId'] ?? null,
            Jenis: $data['Jenis'] ?? null,
            PihakMenyerahkan: $data['PihakMenyerahkan'] ?? null,
            PihakMenerima: $data['PihakMenerima'] ?? null,
            DiserahkanPada: $data['DiserahkanPada'] ?? null,
            DiterimaPada: $data['DiterimaPada'] ?? null,
            Status: $data['Status'] ?? null,
            Catatan: $data['Catatan'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
