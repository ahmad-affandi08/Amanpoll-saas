<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Application\DTO;

final readonly class WaktuKerjaData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $PerintahKerjaId = null,
        public mixed $PenggunaId = null,
        public mixed $MulaiPada = null,
        public mixed $SelesaiPada = null,
        public mixed $DurasiMenit = null,
        public mixed $JenisWaktu = null,
        public mixed $Catatan = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            PerintahKerjaId: $data['PerintahKerjaId'] ?? null,
            PenggunaId: $data['PenggunaId'] ?? null,
            MulaiPada: $data['MulaiPada'] ?? null,
            SelesaiPada: $data['SelesaiPada'] ?? null,
            DurasiMenit: $data['DurasiMenit'] ?? null,
            JenisWaktu: $data['JenisWaktu'] ?? null,
            Catatan: $data['Catatan'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
