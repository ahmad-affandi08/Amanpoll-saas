<?php

declare(strict_types=1);

namespace App\Domain\Langganan\Application\DTO;

final readonly class PembayaranLanggananData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $TagihanLanggananId = null,
        public mixed $PenyediaPembayaran = null,
        public mixed $ReferensiEksternal = null,
        public mixed $Metode = null,
        public mixed $Jumlah = null,
        public mixed $Status = null,
        public mixed $DibayarPada = null,
        public mixed $MuatanData = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            TagihanLanggananId: $data['TagihanLanggananId'] ?? null,
            PenyediaPembayaran: $data['PenyediaPembayaran'] ?? null,
            ReferensiEksternal: $data['ReferensiEksternal'] ?? null,
            Metode: $data['Metode'] ?? null,
            Jumlah: $data['Jumlah'] ?? null,
            Status: $data['Status'] ?? null,
            DibayarPada: $data['DibayarPada'] ?? null,
            MuatanData: $data['MuatanData'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
