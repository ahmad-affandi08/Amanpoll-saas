<?php

declare(strict_types=1);

namespace App\Domain\PreventifInspeksi\Application\DTO;

final readonly class JawabanDaftarPeriksaData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $PelaksanaanDaftarPeriksaId = null,
        public mixed $ButirTemplatDaftarPeriksaId = null,
        public mixed $NilaiTeks = null,
        public mixed $NilaiAngka = null,
        public mixed $NilaiBoolean = null,
        public mixed $NilaiTanggal = null,
        public mixed $NilaiJson = null,
        public mixed $Sesuai = null,
        public mixed $Catatan = null,
        public mixed $DijawabPada = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            PelaksanaanDaftarPeriksaId: $data['PelaksanaanDaftarPeriksaId'] ?? null,
            ButirTemplatDaftarPeriksaId: $data['ButirTemplatDaftarPeriksaId'] ?? null,
            NilaiTeks: $data['NilaiTeks'] ?? null,
            NilaiAngka: $data['NilaiAngka'] ?? null,
            NilaiBoolean: $data['NilaiBoolean'] ?? null,
            NilaiTanggal: $data['NilaiTanggal'] ?? null,
            NilaiJson: $data['NilaiJson'] ?? null,
            Sesuai: $data['Sesuai'] ?? null,
            Catatan: $data['Catatan'] ?? null,
            DijawabPada: $data['DijawabPada'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
