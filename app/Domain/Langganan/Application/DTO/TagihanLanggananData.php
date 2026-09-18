<?php

declare(strict_types=1);

namespace App\Domain\Langganan\Application\DTO;

final readonly class TagihanLanggananData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $LanggananId = null,
        public mixed $Nomor = null,
        public mixed $PeriodeMulai = null,
        public mixed $PeriodeSelesai = null,
        public mixed $JatuhTempo = null,
        public mixed $Subtotal = null,
        public mixed $Pajak = null,
        public mixed $Total = null,
        public mixed $Status = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            LanggananId: $data['LanggananId'] ?? null,
            Nomor: $data['Nomor'] ?? null,
            PeriodeMulai: $data['PeriodeMulai'] ?? null,
            PeriodeSelesai: $data['PeriodeSelesai'] ?? null,
            JatuhTempo: $data['JatuhTempo'] ?? null,
            Subtotal: $data['Subtotal'] ?? null,
            Pajak: $data['Pajak'] ?? null,
            Total: $data['Total'] ?? null,
            Status: $data['Status'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
