<?php

declare(strict_types=1);

namespace App\Domain\PreventifInspeksi\Application\DTO;

final readonly class PelaksanaanDaftarPeriksaData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $TemplatDaftarPeriksaId = null,
        public mixed $PerintahKerjaId = null,
        public mixed $AsetId = null,
        public mixed $DilaksanakanOleh = null,
        public mixed $MulaiPada = null,
        public mixed $SelesaiPada = null,
        public mixed $Status = null,
        public mixed $Skor = null,
        public mixed $Catatan = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            TemplatDaftarPeriksaId: $data['TemplatDaftarPeriksaId'] ?? null,
            PerintahKerjaId: $data['PerintahKerjaId'] ?? null,
            AsetId: $data['AsetId'] ?? null,
            DilaksanakanOleh: $data['DilaksanakanOleh'] ?? null,
            MulaiPada: $data['MulaiPada'] ?? null,
            SelesaiPada: $data['SelesaiPada'] ?? null,
            Status: $data['Status'] ?? null,
            Skor: $data['Skor'] ?? null,
            Catatan: $data['Catatan'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
