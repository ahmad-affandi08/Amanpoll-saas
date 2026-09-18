<?php

declare(strict_types=1);

namespace App\Domain\Persetujuan\Application\DTO;

final readonly class PermintaanPersetujuanData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $AlurPersetujuanId = null,
        public mixed $JenisEntitas = null,
        public mixed $EntitasId = null,
        public mixed $TahapSaatIni = null,
        public mixed $Status = null,
        public mixed $DimintaOleh = null,
        public mixed $DimintaPada = null,
        public mixed $SelesaiPada = null,
        public mixed $DataTambahan = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            AlurPersetujuanId: $data['AlurPersetujuanId'] ?? null,
            JenisEntitas: $data['JenisEntitas'] ?? null,
            EntitasId: $data['EntitasId'] ?? null,
            TahapSaatIni: $data['TahapSaatIni'] ?? null,
            Status: $data['Status'] ?? null,
            DimintaOleh: $data['DimintaOleh'] ?? null,
            DimintaPada: $data['DimintaPada'] ?? null,
            SelesaiPada: $data['SelesaiPada'] ?? null,
            DataTambahan: $data['DataTambahan'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
