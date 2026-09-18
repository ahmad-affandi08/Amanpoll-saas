<?php

declare(strict_types=1);

namespace App\Domain\Sinkronisasi\Application\DTO;

final readonly class PenandaSinkronisasiData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $PerangkatPenggunaId = null,
        public mixed $JenisEntitas = null,
        public mixed $TokenSinkronisasi = null,
        public mixed $TerakhirSinkronPada = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            PerangkatPenggunaId: $data['PerangkatPenggunaId'] ?? null,
            JenisEntitas: $data['JenisEntitas'] ?? null,
            TokenSinkronisasi: $data['TokenSinkronisasi'] ?? null,
            TerakhirSinkronPada: $data['TerakhirSinkronPada'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
