<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Application\DTO;

final readonly class KelompokSukuCadangData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $SukuCadangId = null,
        public mixed $NomorBatch = null,
        public mixed $TanggalProduksi = null,
        public mixed $TanggalKadaluarsa = null,
        public mixed $HargaPerolehan = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            SukuCadangId: $data['SukuCadangId'] ?? null,
            NomorBatch: $data['NomorBatch'] ?? null,
            TanggalProduksi: $data['TanggalProduksi'] ?? null,
            TanggalKadaluarsa: $data['TanggalKadaluarsa'] ?? null,
            HargaPerolehan: $data['HargaPerolehan'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
