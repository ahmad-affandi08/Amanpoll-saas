<?php

declare(strict_types=1);

namespace App\Domain\Langganan\Application\DTO;

final readonly class PaketFiturData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $PaketLanggananId = null,
        public mixed $FiturPaketId = null,
        public mixed $Diizinkan = null,
        public mixed $BatasNilai = null,
        public mixed $NilaiJson = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            PaketLanggananId: $data['PaketLanggananId'] ?? null,
            FiturPaketId: $data['FiturPaketId'] ?? null,
            Diizinkan: $data['Diizinkan'] ?? null,
            BatasNilai: $data['BatasNilai'] ?? null,
            NilaiJson: $data['NilaiJson'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
