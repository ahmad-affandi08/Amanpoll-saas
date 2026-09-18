<?php

declare(strict_types=1);

namespace App\Domain\PreventifInspeksi\Application\DTO;

final readonly class TemplatInspeksiData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $Kode = null,
        public mixed $Nama = null,
        public mixed $KategoriAsetId = null,
        public mixed $TemplatDaftarPeriksaId = null,
        public mixed $IntervalHari = null,
        public mixed $Aktif = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            Kode: $data['Kode'] ?? null,
            Nama: $data['Nama'] ?? null,
            KategoriAsetId: $data['KategoriAsetId'] ?? null,
            TemplatDaftarPeriksaId: $data['TemplatDaftarPeriksaId'] ?? null,
            IntervalHari: $data['IntervalHari'] ?? null,
            Aktif: $data['Aktif'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
