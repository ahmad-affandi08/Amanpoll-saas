<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Application\DTO;

final readonly class WaktuHentiAsetData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $AsetId = null,
        public mixed $PerintahKerjaId = null,
        public mixed $MulaiPada = null,
        public mixed $SelesaiPada = null,
        public mixed $DurasiMenit = null,
        public mixed $Jenis = null,
        public mixed $Alasan = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            AsetId: $data['AsetId'] ?? null,
            PerintahKerjaId: $data['PerintahKerjaId'] ?? null,
            MulaiPada: $data['MulaiPada'] ?? null,
            SelesaiPada: $data['SelesaiPada'] ?? null,
            DurasiMenit: $data['DurasiMenit'] ?? null,
            Jenis: $data['Jenis'] ?? null,
            Alasan: $data['Alasan'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
