<?php

declare(strict_types=1);

namespace App\Domain\Platform\Application\DTO;

final readonly class PerangkatPenggunaData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $PenggunaId = null,
        public mixed $NamaPerangkat = null,
        public mixed $Platform = null,
        public mixed $IdentitasPerangkat = null,
        public mixed $TokenPush = null,
        public mixed $TerakhirSinkronPada = null,
        public mixed $Status = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            PenggunaId: $data['PenggunaId'] ?? null,
            NamaPerangkat: $data['NamaPerangkat'] ?? null,
            Platform: $data['Platform'] ?? null,
            IdentitasPerangkat: $data['IdentitasPerangkat'] ?? null,
            TokenPush: $data['TokenPush'] ?? null,
            TerakhirSinkronPada: $data['TerakhirSinkronPada'] ?? null,
            Status: $data['Status'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
