<?php

declare(strict_types=1);

namespace App\Domain\Platform\Application\DTO;

final readonly class NomorDokumenData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $JenisDokumen = null,
        public mixed $Awalan = null,
        public mixed $FormatNomor = null,
        public mixed $NomorTerakhir = null,
        public mixed $ResetPeriode = null,
        public mixed $PeriodeAktif = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            JenisDokumen: $data['JenisDokumen'] ?? null,
            Awalan: $data['Awalan'] ?? null,
            FormatNomor: $data['FormatNomor'] ?? null,
            NomorTerakhir: $data['NomorTerakhir'] ?? null,
            ResetPeriode: $data['ResetPeriode'] ?? null,
            PeriodeAktif: $data['PeriodeAktif'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
