<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Application\DTO;

final readonly class AturanTingkatLayananData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $TingkatLayananId = null,
        public mixed $Prioritas = null,
        public mixed $MenitRespons = null,
        public mixed $MenitMulaiPengerjaan = null,
        public mixed $MenitPenyelesaian = null,
        public mixed $MenghitungJamKerja = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            TingkatLayananId: $data['TingkatLayananId'] ?? null,
            Prioritas: $data['Prioritas'] ?? null,
            MenitRespons: $data['MenitRespons'] ?? null,
            MenitMulaiPengerjaan: $data['MenitMulaiPengerjaan'] ?? null,
            MenitPenyelesaian: $data['MenitPenyelesaian'] ?? null,
            MenghitungJamKerja: $data['MenghitungJamKerja'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
