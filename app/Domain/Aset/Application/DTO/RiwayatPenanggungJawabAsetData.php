<?php

declare(strict_types=1);

namespace App\Domain\Aset\Application\DTO;

final readonly class RiwayatPenanggungJawabAsetData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $AsetId = null,
        public mixed $PenggunaId = null,
        public mixed $UnitOrganisasiId = null,
        public mixed $MulaiPada = null,
        public mixed $SelesaiPada = null,
        public mixed $Catatan = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            AsetId: $data['AsetId'] ?? null,
            PenggunaId: $data['PenggunaId'] ?? null,
            UnitOrganisasiId: $data['UnitOrganisasiId'] ?? null,
            MulaiPada: $data['MulaiPada'] ?? null,
            SelesaiPada: $data['SelesaiPada'] ?? null,
            Catatan: $data['Catatan'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
