<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Application\DTO;

final readonly class GudangData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $LokasiId = null,
        public mixed $Kode = null,
        public mixed $Nama = null,
        public mixed $PenanggungJawabId = null,
        public mixed $Status = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            LokasiId: $data['LokasiId'] ?? null,
            Kode: $data['Kode'] ?? null,
            Nama: $data['Nama'] ?? null,
            PenanggungJawabId: $data['PenanggungJawabId'] ?? null,
            Status: $data['Status'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
