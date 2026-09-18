<?php

declare(strict_types=1);

namespace App\Domain\IntegrasiAudit\Application\DTO;

final readonly class KunciIdempotensiData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $Kunci = null,
        public mixed $Rute = null,
        public mixed $HashPermintaan = null,
        public mixed $StatusHttp = null,
        public mixed $Respons = null,
        public mixed $KadaluarsaPada = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            Kunci: $data['Kunci'] ?? null,
            Rute: $data['Rute'] ?? null,
            HashPermintaan: $data['HashPermintaan'] ?? null,
            StatusHttp: $data['StatusHttp'] ?? null,
            Respons: $data['Respons'] ?? null,
            KadaluarsaPada: $data['KadaluarsaPada'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
