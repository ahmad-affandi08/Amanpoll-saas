<?php

declare(strict_types=1);

namespace App\Domain\IntegrasiAudit\Application\DTO;

final readonly class CatatanAksesData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $PenggunaId = null,
        public mixed $Jenis = null,
        public mixed $AlamatIp = null,
        public mixed $AgenPengguna = null,
        public mixed $Berhasil = null,
        public mixed $AlasanGagal = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            PenggunaId: $data['PenggunaId'] ?? null,
            Jenis: $data['Jenis'] ?? null,
            AlamatIp: $data['AlamatIp'] ?? null,
            AgenPengguna: $data['AgenPengguna'] ?? null,
            Berhasil: $data['Berhasil'] ?? null,
            AlasanGagal: $data['AlasanGagal'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
