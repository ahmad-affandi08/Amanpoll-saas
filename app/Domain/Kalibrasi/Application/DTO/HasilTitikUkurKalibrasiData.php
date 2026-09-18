<?php

declare(strict_types=1);

namespace App\Domain\Kalibrasi\Application\DTO;

final readonly class HasilTitikUkurKalibrasiData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $PelaksanaanKalibrasiId = null,
        public mixed $TitikUkurKalibrasiId = null,
        public mixed $NamaTitik = null,
        public mixed $NilaiReferensi = null,
        public mixed $NilaiTerukur = null,
        public mixed $Koreksi = null,
        public mixed $Ketidakpastian = null,
        public mixed $Satuan = null,
        public mixed $Hasil = null,
        public mixed $Catatan = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            PelaksanaanKalibrasiId: $data['PelaksanaanKalibrasiId'] ?? null,
            TitikUkurKalibrasiId: $data['TitikUkurKalibrasiId'] ?? null,
            NamaTitik: $data['NamaTitik'] ?? null,
            NilaiReferensi: $data['NilaiReferensi'] ?? null,
            NilaiTerukur: $data['NilaiTerukur'] ?? null,
            Koreksi: $data['Koreksi'] ?? null,
            Ketidakpastian: $data['Ketidakpastian'] ?? null,
            Satuan: $data['Satuan'] ?? null,
            Hasil: $data['Hasil'] ?? null,
            Catatan: $data['Catatan'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
