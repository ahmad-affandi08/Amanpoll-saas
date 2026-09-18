<?php

declare(strict_types=1);

namespace App\Domain\Kepatuhan\Application\DTO;

final readonly class PemetaanDataEksternalData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $IntegrasiEksternalId = null,
        public mixed $JenisEntitas = null,
        public mixed $EntitasId = null,
        public mixed $KodeEksternal = null,
        public mixed $DataTambahan = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            IntegrasiEksternalId: $data['IntegrasiEksternalId'] ?? null,
            JenisEntitas: $data['JenisEntitas'] ?? null,
            EntitasId: $data['EntitasId'] ?? null,
            KodeEksternal: $data['KodeEksternal'] ?? null,
            DataTambahan: $data['DataTambahan'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
