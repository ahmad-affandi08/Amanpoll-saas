<?php

declare(strict_types=1);

namespace App\Domain\Kontrak\Application\DTO;

final readonly class LayananKontrakData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $KontrakId = null,
        public mixed $Nama = null,
        public mixed $Deskripsi = null,
        public mixed $Kuota = null,
        public mixed $Satuan = null,
        public mixed $Terpakai = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            KontrakId: $data['KontrakId'] ?? null,
            Nama: $data['Nama'] ?? null,
            Deskripsi: $data['Deskripsi'] ?? null,
            Kuota: $data['Kuota'] ?? null,
            Satuan: $data['Satuan'] ?? null,
            Terpakai: $data['Terpakai'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
