<?php

declare(strict_types=1);

namespace App\Domain\Kepatuhan\Application\DTO;

final readonly class SertifikasiAsetData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $AsetId = null,
        public mixed $JenisSertifikasi = null,
        public mixed $NomorSertifikat = null,
        public mixed $Penerbit = null,
        public mixed $TerbitPada = null,
        public mixed $BerlakuSampai = null,
        public mixed $Status = null,
        public mixed $BerkasId = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            AsetId: $data['AsetId'] ?? null,
            JenisSertifikasi: $data['JenisSertifikasi'] ?? null,
            NomorSertifikat: $data['NomorSertifikat'] ?? null,
            Penerbit: $data['Penerbit'] ?? null,
            TerbitPada: $data['TerbitPada'] ?? null,
            BerlakuSampai: $data['BerlakuSampai'] ?? null,
            Status: $data['Status'] ?? null,
            BerkasId: $data['BerkasId'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
