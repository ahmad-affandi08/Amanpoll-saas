<?php

declare(strict_types=1);

namespace App\Domain\Aset\Application\DTO;

final readonly class RiwayatLokasiAsetData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $AsetId = null,
        public mixed $LokasiAsalId = null,
        public mixed $LokasiTujuanId = null,
        public mixed $JenisPerpindahan = null,
        public mixed $ReferensiJenis = null,
        public mixed $ReferensiId = null,
        public mixed $Alasan = null,
        public mixed $DipindahkanOleh = null,
        public mixed $DipindahkanPada = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            AsetId: $data['AsetId'] ?? null,
            LokasiAsalId: $data['LokasiAsalId'] ?? null,
            LokasiTujuanId: $data['LokasiTujuanId'] ?? null,
            JenisPerpindahan: $data['JenisPerpindahan'] ?? null,
            ReferensiJenis: $data['ReferensiJenis'] ?? null,
            ReferensiId: $data['ReferensiId'] ?? null,
            Alasan: $data['Alasan'] ?? null,
            DipindahkanOleh: $data['DipindahkanOleh'] ?? null,
            DipindahkanPada: $data['DipindahkanPada'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
