<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Application\DTO;

final readonly class MutasiStokData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $Nomor = null,
        public mixed $Jenis = null,
        public mixed $GudangAsalId = null,
        public mixed $GudangTujuanId = null,
        public mixed $ReferensiJenis = null,
        public mixed $ReferensiId = null,
        public mixed $Tanggal = null,
        public mixed $Status = null,
        public mixed $Catatan = null,
        public mixed $DibuatOleh = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            Nomor: $data['Nomor'] ?? null,
            Jenis: $data['Jenis'] ?? null,
            GudangAsalId: $data['GudangAsalId'] ?? null,
            GudangTujuanId: $data['GudangTujuanId'] ?? null,
            ReferensiJenis: $data['ReferensiJenis'] ?? null,
            ReferensiId: $data['ReferensiId'] ?? null,
            Tanggal: $data['Tanggal'] ?? null,
            Status: $data['Status'] ?? null,
            Catatan: $data['Catatan'] ?? null,
            DibuatOleh: $data['DibuatOleh'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
