<?php

declare(strict_types=1);

namespace App\Domain\Langganan\Application\DTO;

final readonly class FiturPaketData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $Kode = null,
        public mixed $Nama = null,
        public mixed $Deskripsi = null,
        public mixed $TipeBatas = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            Kode: $data['Kode'] ?? null,
            Nama: $data['Nama'] ?? null,
            Deskripsi: $data['Deskripsi'] ?? null,
            TipeBatas: $data['TipeBatas'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
