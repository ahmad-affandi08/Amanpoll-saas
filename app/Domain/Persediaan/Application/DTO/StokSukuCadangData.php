<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Application\DTO;

final readonly class StokSukuCadangData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $GudangId = null,
        public mixed $LokasiGudangId = null,
        public mixed $SukuCadangId = null,
        public mixed $KelompokSukuCadangId = null,
        public mixed $JumlahTersedia = null,
        public mixed $JumlahDipesan = null,
        public mixed $JumlahDitahan = null,
        public mixed $Versi = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            GudangId: $data['GudangId'] ?? null,
            LokasiGudangId: $data['LokasiGudangId'] ?? null,
            SukuCadangId: $data['SukuCadangId'] ?? null,
            KelompokSukuCadangId: $data['KelompokSukuCadangId'] ?? null,
            JumlahTersedia: $data['JumlahTersedia'] ?? null,
            JumlahDipesan: $data['JumlahDipesan'] ?? null,
            JumlahDitahan: $data['JumlahDitahan'] ?? null,
            Versi: $data['Versi'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
