<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Application\DTO;

final readonly class PemakaianSukuCadangData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $PerintahKerjaId = null,
        public mixed $SukuCadangId = null,
        public mixed $GudangId = null,
        public mixed $KelompokSukuCadangId = null,
        public mixed $Jumlah = null,
        public mixed $HargaSatuan = null,
        public mixed $MutasiStokId = null,
        public mixed $DipakaiOleh = null,
        public mixed $DipakaiPada = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            PerintahKerjaId: $data['PerintahKerjaId'] ?? null,
            SukuCadangId: $data['SukuCadangId'] ?? null,
            GudangId: $data['GudangId'] ?? null,
            KelompokSukuCadangId: $data['KelompokSukuCadangId'] ?? null,
            Jumlah: $data['Jumlah'] ?? null,
            HargaSatuan: $data['HargaSatuan'] ?? null,
            MutasiStokId: $data['MutasiStokId'] ?? null,
            DipakaiOleh: $data['DipakaiOleh'] ?? null,
            DipakaiPada: $data['DipakaiPada'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
