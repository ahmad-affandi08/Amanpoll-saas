<?php

declare(strict_types=1);

namespace App\Domain\PreventifInspeksi\Application\DTO;

final readonly class InspeksiData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $Nomor = null,
        public mixed $TemplatInspeksiId = null,
        public mixed $AsetId = null,
        public mixed $PelaksanaanDaftarPeriksaId = null,
        public mixed $DijadwalkanPada = null,
        public mixed $DilaksanakanPada = null,
        public mixed $Status = null,
        public mixed $Hasil = null,
        public mixed $Temuan = null,
        public mixed $TindakLanjut = null,
        public mixed $PerintahKerjaId = null,
        public mixed $DilaksanakanOleh = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            Nomor: $data['Nomor'] ?? null,
            TemplatInspeksiId: $data['TemplatInspeksiId'] ?? null,
            AsetId: $data['AsetId'] ?? null,
            PelaksanaanDaftarPeriksaId: $data['PelaksanaanDaftarPeriksaId'] ?? null,
            DijadwalkanPada: $data['DijadwalkanPada'] ?? null,
            DilaksanakanPada: $data['DilaksanakanPada'] ?? null,
            Status: $data['Status'] ?? null,
            Hasil: $data['Hasil'] ?? null,
            Temuan: $data['Temuan'] ?? null,
            TindakLanjut: $data['TindakLanjut'] ?? null,
            PerintahKerjaId: $data['PerintahKerjaId'] ?? null,
            DilaksanakanOleh: $data['DilaksanakanOleh'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
