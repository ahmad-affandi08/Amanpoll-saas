<?php

declare(strict_types=1);

namespace App\Domain\Aset\Application\DTO;

final readonly class RelasiAsetData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $AsetIndukId = null,
        public mixed $AsetAnakId = null,
        public mixed $JenisRelasi = null,
        public mixed $Jumlah = null,
        public mixed $MulaiPada = null,
        public mixed $SelesaiPada = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            AsetIndukId: $data['AsetIndukId'] ?? null,
            AsetAnakId: $data['AsetAnakId'] ?? null,
            JenisRelasi: $data['JenisRelasi'] ?? null,
            Jumlah: $data['Jumlah'] ?? null,
            MulaiPada: $data['MulaiPada'] ?? null,
            SelesaiPada: $data['SelesaiPada'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
