<?php

declare(strict_types=1);

namespace App\Domain\PreventifInspeksi\Application\DTO;

final readonly class JadwalPemeliharaanData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $RencanaPemeliharaanAsetId = null,
        public mixed $PerintahKerjaId = null,
        public mixed $TanggalJadwal = null,
        public mixed $Status = null,
        public mixed $DihasilkanOtomatis = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            RencanaPemeliharaanAsetId: $data['RencanaPemeliharaanAsetId'] ?? null,
            PerintahKerjaId: $data['PerintahKerjaId'] ?? null,
            TanggalJadwal: $data['TanggalJadwal'] ?? null,
            Status: $data['Status'] ?? null,
            DihasilkanOtomatis: $data['DihasilkanOtomatis'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
