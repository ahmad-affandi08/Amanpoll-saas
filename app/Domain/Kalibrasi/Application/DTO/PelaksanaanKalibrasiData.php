<?php

declare(strict_types=1);

namespace App\Domain\Kalibrasi\Application\DTO;

final readonly class PelaksanaanKalibrasiData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $Nomor = null,
        public mixed $RencanaKalibrasiId = null,
        public mixed $AsetId = null,
        public mixed $JenisKalibrasiId = null,
        public mixed $PenyediaId = null,
        public mixed $PerintahKerjaId = null,
        public mixed $TanggalKalibrasi = null,
        public mixed $TanggalBerlakuSampai = null,
        public mixed $Hasil = null,
        public mixed $NomorSertifikat = null,
        public mixed $Laboratorium = null,
        public mixed $KondisiLingkungan = null,
        public mixed $Catatan = null,
        public mixed $DilaksanakanOleh = null,
        public mixed $DiverifikasiOleh = null,
        public mixed $DiverifikasiPada = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            Nomor: $data['Nomor'] ?? null,
            RencanaKalibrasiId: $data['RencanaKalibrasiId'] ?? null,
            AsetId: $data['AsetId'] ?? null,
            JenisKalibrasiId: $data['JenisKalibrasiId'] ?? null,
            PenyediaId: $data['PenyediaId'] ?? null,
            PerintahKerjaId: $data['PerintahKerjaId'] ?? null,
            TanggalKalibrasi: $data['TanggalKalibrasi'] ?? null,
            TanggalBerlakuSampai: $data['TanggalBerlakuSampai'] ?? null,
            Hasil: $data['Hasil'] ?? null,
            NomorSertifikat: $data['NomorSertifikat'] ?? null,
            Laboratorium: $data['Laboratorium'] ?? null,
            KondisiLingkungan: $data['KondisiLingkungan'] ?? null,
            Catatan: $data['Catatan'] ?? null,
            DilaksanakanOleh: $data['DilaksanakanOleh'] ?? null,
            DiverifikasiOleh: $data['DiverifikasiOleh'] ?? null,
            DiverifikasiPada: $data['DiverifikasiPada'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
