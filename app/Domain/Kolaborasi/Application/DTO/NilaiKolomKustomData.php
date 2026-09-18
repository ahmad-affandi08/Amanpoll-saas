<?php

declare(strict_types=1);

namespace App\Domain\Kolaborasi\Application\DTO;

final readonly class NilaiKolomKustomData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $DefinisiKolomKustomId = null,
        public mixed $JenisEntitas = null,
        public mixed $EntitasId = null,
        public mixed $Nilai = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            DefinisiKolomKustomId: $data['DefinisiKolomKustomId'] ?? null,
            JenisEntitas: $data['JenisEntitas'] ?? null,
            EntitasId: $data['EntitasId'] ?? null,
            Nilai: $data['Nilai'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
