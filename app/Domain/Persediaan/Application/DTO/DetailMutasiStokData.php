<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Application\DTO;

final readonly class DetailMutasiStokData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $MutasiStokId = null,
        public mixed $SukuCadangId = null,
        public mixed $KelompokSukuCadangId = null,
        public mixed $Jumlah = null,
        public mixed $HargaSatuan = null,
        public mixed $LokasiGudangAsalId = null,
        public mixed $LokasiGudangTujuanId = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            MutasiStokId: $data['MutasiStokId'] ?? null,
            SukuCadangId: $data['SukuCadangId'] ?? null,
            KelompokSukuCadangId: $data['KelompokSukuCadangId'] ?? null,
            Jumlah: $data['Jumlah'] ?? null,
            HargaSatuan: $data['HargaSatuan'] ?? null,
            LokasiGudangAsalId: $data['LokasiGudangAsalId'] ?? null,
            LokasiGudangTujuanId: $data['LokasiGudangTujuanId'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
