<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Application\DTO;

final readonly class PerintahKerjaData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $Nomor = null,
        public mixed $KeluhanId = null,
        public mixed $TingkatLayananId = null,
        public mixed $Jenis = null,
        public mixed $Judul = null,
        public mixed $Deskripsi = null,
        public mixed $Prioritas = null,
        public mixed $Status = null,
        public mixed $LokasiId = null,
        public mixed $UnitOrganisasiId = null,
        public mixed $DijadwalkanMulaiPada = null,
        public mixed $DijadwalkanSelesaiPada = null,
        public mixed $DiterimaPada = null,
        public mixed $DimulaiPada = null,
        public mixed $DiselesaikanPada = null,
        public mixed $DitutupPada = null,
        public mixed $BatasResponsPada = null,
        public mixed $BatasPenyelesaianPada = null,
        public mixed $PersentaseSelesai = null,
        public mixed $MembutuhkanWaktuHenti = null,
        public mixed $MembutuhkanPersetujuan = null,
        public mixed $RingkasanPenyelesaian = null,
        public mixed $DibuatOleh = null,
        public mixed $Versi = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            Nomor: $data['Nomor'] ?? null,
            KeluhanId: $data['KeluhanId'] ?? null,
            TingkatLayananId: $data['TingkatLayananId'] ?? null,
            Jenis: $data['Jenis'] ?? null,
            Judul: $data['Judul'] ?? null,
            Deskripsi: $data['Deskripsi'] ?? null,
            Prioritas: $data['Prioritas'] ?? null,
            Status: $data['Status'] ?? null,
            LokasiId: $data['LokasiId'] ?? null,
            UnitOrganisasiId: $data['UnitOrganisasiId'] ?? null,
            DijadwalkanMulaiPada: $data['DijadwalkanMulaiPada'] ?? null,
            DijadwalkanSelesaiPada: $data['DijadwalkanSelesaiPada'] ?? null,
            DiterimaPada: $data['DiterimaPada'] ?? null,
            DimulaiPada: $data['DimulaiPada'] ?? null,
            DiselesaikanPada: $data['DiselesaikanPada'] ?? null,
            DitutupPada: $data['DitutupPada'] ?? null,
            BatasResponsPada: $data['BatasResponsPada'] ?? null,
            BatasPenyelesaianPada: $data['BatasPenyelesaianPada'] ?? null,
            PersentaseSelesai: $data['PersentaseSelesai'] ?? null,
            MembutuhkanWaktuHenti: $data['MembutuhkanWaktuHenti'] ?? null,
            MembutuhkanPersetujuan: $data['MembutuhkanPersetujuan'] ?? null,
            RingkasanPenyelesaian: $data['RingkasanPenyelesaian'] ?? null,
            DibuatOleh: $data['DibuatOleh'] ?? null,
            Versi: $data['Versi'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
