<?php

declare(strict_types=1);

namespace App\Domain\Aset\Application\DTO;

final readonly class MeterAsetData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $AsetId = null,
        public mixed $Nama = null,
        public mixed $Satuan = null,
        public mixed $Jenis = null,
        public mixed $NilaiAwal = null,
        public mixed $Aktif = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            AsetId: $data['AsetId'] ?? null,
            Nama: $data['Nama'] ?? null,
            Satuan: $data['Satuan'] ?? null,
            Jenis: $data['Jenis'] ?? null,
            NilaiAwal: $data['NilaiAwal'] ?? null,
            Aktif: $data['Aktif'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
