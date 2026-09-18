<?php

declare(strict_types=1);

namespace App\Domain\Kepatuhan\Application\DTO;

final readonly class IntegrasiEksternalData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $Kode = null,
        public mixed $Nama = null,
        public mixed $Jenis = null,
        public mixed $UrlDasar = null,
        public mixed $MetodeAutentikasi = null,
        public mixed $KonfigurasiTerenkripsi = null,
        public mixed $Status = null,
        public mixed $TerakhirSinkronPada = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            Kode: $data['Kode'] ?? null,
            Nama: $data['Nama'] ?? null,
            Jenis: $data['Jenis'] ?? null,
            UrlDasar: $data['UrlDasar'] ?? null,
            MetodeAutentikasi: $data['MetodeAutentikasi'] ?? null,
            KonfigurasiTerenkripsi: $data['KonfigurasiTerenkripsi'] ?? null,
            Status: $data['Status'] ?? null,
            TerakhirSinkronPada: $data['TerakhirSinkronPada'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
