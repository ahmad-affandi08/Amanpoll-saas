<?php

declare(strict_types=1);

namespace App\Domain\Notifikasi\Application\DTO;

final readonly class TemplatNotifikasiData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $Kode = null,
        public mixed $Kanal = null,
        public mixed $JudulTemplat = null,
        public mixed $IsiTemplat = null,
        public mixed $Variabel = null,
        public mixed $Aktif = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            Kode: $data['Kode'] ?? null,
            Kanal: $data['Kanal'] ?? null,
            JudulTemplat: $data['JudulTemplat'] ?? null,
            IsiTemplat: $data['IsiTemplat'] ?? null,
            Variabel: $data['Variabel'] ?? null,
            Aktif: $data['Aktif'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
