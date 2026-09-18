<?php

declare(strict_types=1);

namespace App\Domain\PreventifInspeksi\Application\DTO;

final readonly class RencanaPemeliharaanData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $Kode = null,
        public mixed $Nama = null,
        public mixed $Jenis = null,
        public mixed $TemplatDaftarPeriksaId = null,
        public mixed $Prioritas = null,
        public mixed $StrategiJadwal = null,
        public mixed $IntervalNilai = null,
        public mixed $IntervalSatuan = null,
        public mixed $BerdasarkanMeter = null,
        public mixed $AmbangMeter = null,
        public mixed $ToleransiHari = null,
        public mixed $BuatPerintahKerjaHariSebelum = null,
        public mixed $Aktif = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            Kode: $data['Kode'] ?? null,
            Nama: $data['Nama'] ?? null,
            Jenis: $data['Jenis'] ?? null,
            TemplatDaftarPeriksaId: $data['TemplatDaftarPeriksaId'] ?? null,
            Prioritas: $data['Prioritas'] ?? null,
            StrategiJadwal: $data['StrategiJadwal'] ?? null,
            IntervalNilai: $data['IntervalNilai'] ?? null,
            IntervalSatuan: $data['IntervalSatuan'] ?? null,
            BerdasarkanMeter: $data['BerdasarkanMeter'] ?? null,
            AmbangMeter: $data['AmbangMeter'] ?? null,
            ToleransiHari: $data['ToleransiHari'] ?? null,
            BuatPerintahKerjaHariSebelum: $data['BuatPerintahKerjaHariSebelum'] ?? null,
            Aktif: $data['Aktif'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
