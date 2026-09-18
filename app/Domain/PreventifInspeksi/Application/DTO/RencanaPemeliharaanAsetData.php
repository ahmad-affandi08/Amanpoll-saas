<?php

declare(strict_types=1);

namespace App\Domain\PreventifInspeksi\Application\DTO;

final readonly class RencanaPemeliharaanAsetData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $RencanaPemeliharaanId = null,
        public mixed $AsetId = null,
        public mixed $TanggalMulai = null,
        public mixed $TanggalBerikutnya = null,
        public mixed $NilaiMeterBerikutnya = null,
        public mixed $TerakhirDilaksanakanPada = null,
        public mixed $Aktif = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            RencanaPemeliharaanId: $data['RencanaPemeliharaanId'] ?? null,
            AsetId: $data['AsetId'] ?? null,
            TanggalMulai: $data['TanggalMulai'] ?? null,
            TanggalBerikutnya: $data['TanggalBerikutnya'] ?? null,
            NilaiMeterBerikutnya: $data['NilaiMeterBerikutnya'] ?? null,
            TerakhirDilaksanakanPada: $data['TerakhirDilaksanakanPada'] ?? null,
            Aktif: $data['Aktif'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
