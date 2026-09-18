<?php

declare(strict_types=1);

namespace App\Domain\SiklusAset\Application\DTO;

final readonly class DetailSerahTerimaAsetData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $SerahTerimaAsetId = null,
        public mixed $AsetId = null,
        public mixed $KondisiSaatDiserahkan = null,
        public mixed $KondisiSaatDiterima = null,
        public mixed $Catatan = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            SerahTerimaAsetId: $data['SerahTerimaAsetId'] ?? null,
            AsetId: $data['AsetId'] ?? null,
            KondisiSaatDiserahkan: $data['KondisiSaatDiserahkan'] ?? null,
            KondisiSaatDiterima: $data['KondisiSaatDiterima'] ?? null,
            Catatan: $data['Catatan'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
