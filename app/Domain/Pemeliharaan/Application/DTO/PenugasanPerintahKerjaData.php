<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Application\DTO;

final readonly class PenugasanPerintahKerjaData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $PerintahKerjaId = null,
        public mixed $PenggunaId = null,
        public mixed $PeranTugas = null,
        public mixed $DitugaskanOleh = null,
        public mixed $DitugaskanPada = null,
        public mixed $DiterimaPada = null,
        public mixed $SelesaiPada = null,
        public mixed $Status = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            PerintahKerjaId: $data['PerintahKerjaId'] ?? null,
            PenggunaId: $data['PenggunaId'] ?? null,
            PeranTugas: $data['PeranTugas'] ?? null,
            DitugaskanOleh: $data['DitugaskanOleh'] ?? null,
            DitugaskanPada: $data['DitugaskanPada'] ?? null,
            DiterimaPada: $data['DiterimaPada'] ?? null,
            SelesaiPada: $data['SelesaiPada'] ?? null,
            Status: $data['Status'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
