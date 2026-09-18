<?php

declare(strict_types=1);

namespace App\Domain\Persetujuan\Application\DTO;

final readonly class KeputusanPersetujuanData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $PermintaanPersetujuanId = null,
        public mixed $TahapPersetujuanId = null,
        public mixed $PenyetujuId = null,
        public mixed $Keputusan = null,
        public mixed $Catatan = null,
        public mixed $DiputuskanPada = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            PermintaanPersetujuanId: $data['PermintaanPersetujuanId'] ?? null,
            TahapPersetujuanId: $data['TahapPersetujuanId'] ?? null,
            PenyetujuId: $data['PenyetujuId'] ?? null,
            Keputusan: $data['Keputusan'] ?? null,
            Catatan: $data['Catatan'] ?? null,
            DiputuskanPada: $data['DiputuskanPada'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
