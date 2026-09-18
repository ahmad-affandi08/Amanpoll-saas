<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Application\DTO;

final readonly class PerintahKerjaAsetData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $PerintahKerjaId = null,
        public mixed $AsetId = null,
        public mixed $Utama = null,
        public mixed $KondisiAwal = null,
        public mixed $KondisiAkhir = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            PerintahKerjaId: $data['PerintahKerjaId'] ?? null,
            AsetId: $data['AsetId'] ?? null,
            Utama: $data['Utama'] ?? null,
            KondisiAwal: $data['KondisiAwal'] ?? null,
            KondisiAkhir: $data['KondisiAkhir'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
