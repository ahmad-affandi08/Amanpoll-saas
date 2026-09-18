<?php

declare(strict_types=1);

namespace App\Domain\Platform\Application\DTO;

final readonly class PenggunaData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $UnitOrganisasiId = null,
        public mixed $Nama = null,
        public mixed $Email = null,
        public mixed $Telepon = null,
        public mixed $KataSandi = null,
        public mixed $EmailTerverifikasiPada = null,
        public mixed $AvatarUrl = null,
        public mixed $NomorPegawai = null,
        public mixed $Jabatan = null,
        public mixed $JenisPengguna = null,
        public mixed $Status = null,
        public mixed $TerakhirMasukPada = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            UnitOrganisasiId: $data['UnitOrganisasiId'] ?? null,
            Nama: $data['Nama'] ?? null,
            Email: $data['Email'] ?? null,
            Telepon: $data['Telepon'] ?? null,
            KataSandi: $data['KataSandi'] ?? null,
            EmailTerverifikasiPada: $data['EmailTerverifikasiPada'] ?? null,
            AvatarUrl: $data['AvatarUrl'] ?? null,
            NomorPegawai: $data['NomorPegawai'] ?? null,
            Jabatan: $data['Jabatan'] ?? null,
            JenisPengguna: $data['JenisPengguna'] ?? null,
            Status: $data['Status'] ?? null,
            TerakhirMasukPada: $data['TerakhirMasukPada'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
