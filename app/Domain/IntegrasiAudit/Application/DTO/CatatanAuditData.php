<?php

declare(strict_types=1);

namespace App\Domain\IntegrasiAudit\Application\DTO;

final readonly class CatatanAuditData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $PenggunaId = null,
        public mixed $Aksi = null,
        public mixed $JenisEntitas = null,
        public mixed $EntitasId = null,
        public mixed $DataSebelum = null,
        public mixed $DataSesudah = null,
        public mixed $AlamatIp = null,
        public mixed $AgenPengguna = null,
        public mixed $KorelasiId = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            PenggunaId: $data['PenggunaId'] ?? null,
            Aksi: $data['Aksi'] ?? null,
            JenisEntitas: $data['JenisEntitas'] ?? null,
            EntitasId: $data['EntitasId'] ?? null,
            DataSebelum: $data['DataSebelum'] ?? null,
            DataSesudah: $data['DataSesudah'] ?? null,
            AlamatIp: $data['AlamatIp'] ?? null,
            AgenPengguna: $data['AgenPengguna'] ?? null,
            KorelasiId: $data['KorelasiId'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
