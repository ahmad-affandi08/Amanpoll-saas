<?php

declare(strict_types=1);

namespace App\Domain\Kolaborasi\Application\DTO;

final readonly class LampiranEntitasData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $JenisEntitas = null,
        public mixed $EntitasId = null,
        public mixed $BerkasId = null,
        public mixed $Kategori = null,
        public mixed $Keterangan = null,
        public mixed $DibuatOleh = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            JenisEntitas: $data['JenisEntitas'] ?? null,
            EntitasId: $data['EntitasId'] ?? null,
            BerkasId: $data['BerkasId'] ?? null,
            Kategori: $data['Kategori'] ?? null,
            Keterangan: $data['Keterangan'] ?? null,
            DibuatOleh: $data['DibuatOleh'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
