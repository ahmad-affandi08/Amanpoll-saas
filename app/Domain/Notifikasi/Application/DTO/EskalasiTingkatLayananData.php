<?php

declare(strict_types=1);

namespace App\Domain\Notifikasi\Application\DTO;

final readonly class EskalasiTingkatLayananData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $TingkatLayananId = null,
        public mixed $Tahap = null,
        public mixed $SetelahMenit = null,
        public mixed $PeranId = null,
        public mixed $PenggunaId = null,
        public mixed $Kanal = null,
        public mixed $Aktif = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            TingkatLayananId: $data['TingkatLayananId'] ?? null,
            Tahap: $data['Tahap'] ?? null,
            SetelahMenit: $data['SetelahMenit'] ?? null,
            PeranId: $data['PeranId'] ?? null,
            PenggunaId: $data['PenggunaId'] ?? null,
            Kanal: $data['Kanal'] ?? null,
            Aktif: $data['Aktif'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
