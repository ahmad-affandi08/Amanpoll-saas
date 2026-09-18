<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Application\DTO;

final readonly class KompatibilitasSukuCadangData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $SukuCadangId = null,
        public mixed $KategoriAsetId = null,
        public mixed $ModelAsetId = null,
        public mixed $AsetId = null,
        public mixed $Catatan = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            SukuCadangId: $data['SukuCadangId'] ?? null,
            KategoriAsetId: $data['KategoriAsetId'] ?? null,
            ModelAsetId: $data['ModelAsetId'] ?? null,
            AsetId: $data['AsetId'] ?? null,
            Catatan: $data['Catatan'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
