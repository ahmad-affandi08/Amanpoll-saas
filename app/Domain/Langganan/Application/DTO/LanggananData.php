<?php

declare(strict_types=1);

namespace App\Domain\Langganan\Application\DTO;

final readonly class LanggananData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $PaketLanggananId = null,
        public mixed $Siklus = null,
        public mixed $MulaiPada = null,
        public mixed $BerakhirPada = null,
        public mixed $UjiCobaSampai = null,
        public mixed $Status = null,
        public mixed $BatalPada = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            PaketLanggananId: $data['PaketLanggananId'] ?? null,
            Siklus: $data['Siklus'] ?? null,
            MulaiPada: $data['MulaiPada'] ?? null,
            BerakhirPada: $data['BerakhirPada'] ?? null,
            UjiCobaSampai: $data['UjiCobaSampai'] ?? null,
            Status: $data['Status'] ?? null,
            BatalPada: $data['BatalPada'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
