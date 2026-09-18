<?php

declare(strict_types=1);

namespace App\Domain\Kalibrasi\Application\DTO;

final readonly class RencanaKalibrasiData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $AsetId = null,
        public mixed $JenisKalibrasiId = null,
        public mixed $PenyediaId = null,
        public mixed $IntervalHari = null,
        public mixed $TanggalMulai = null,
        public mixed $TanggalBerikutnya = null,
        public mixed $PeringatanHariSebelum = null,
        public mixed $Aktif = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            AsetId: $data['AsetId'] ?? null,
            JenisKalibrasiId: $data['JenisKalibrasiId'] ?? null,
            PenyediaId: $data['PenyediaId'] ?? null,
            IntervalHari: $data['IntervalHari'] ?? null,
            TanggalMulai: $data['TanggalMulai'] ?? null,
            TanggalBerikutnya: $data['TanggalBerikutnya'] ?? null,
            PeringatanHariSebelum: $data['PeringatanHariSebelum'] ?? null,
            Aktif: $data['Aktif'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
