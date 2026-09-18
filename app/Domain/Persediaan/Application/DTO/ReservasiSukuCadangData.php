<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Application\DTO;

final readonly class ReservasiSukuCadangData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $PerintahKerjaId = null,
        public mixed $GudangId = null,
        public mixed $SukuCadangId = null,
        public mixed $Jumlah = null,
        public mixed $Status = null,
        public mixed $KadaluarsaPada = null,
        public mixed $DibuatOleh = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            PerintahKerjaId: $data['PerintahKerjaId'] ?? null,
            GudangId: $data['GudangId'] ?? null,
            SukuCadangId: $data['SukuCadangId'] ?? null,
            Jumlah: $data['Jumlah'] ?? null,
            Status: $data['Status'] ?? null,
            KadaluarsaPada: $data['KadaluarsaPada'] ?? null,
            DibuatOleh: $data['DibuatOleh'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
