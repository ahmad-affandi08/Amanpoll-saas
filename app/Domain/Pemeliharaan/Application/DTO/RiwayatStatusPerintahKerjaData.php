<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Application\DTO;

final readonly class RiwayatStatusPerintahKerjaData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $PerintahKerjaId = null,
        public mixed $StatusSebelum = null,
        public mixed $StatusSesudah = null,
        public mixed $Catatan = null,
        public mixed $DiubahOleh = null,
        public mixed $DiubahPada = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            PerintahKerjaId: $data['PerintahKerjaId'] ?? null,
            StatusSebelum: $data['StatusSebelum'] ?? null,
            StatusSesudah: $data['StatusSesudah'] ?? null,
            Catatan: $data['Catatan'] ?? null,
            DiubahOleh: $data['DiubahOleh'] ?? null,
            DiubahPada: $data['DiubahPada'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
