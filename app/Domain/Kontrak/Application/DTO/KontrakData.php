<?php

declare(strict_types=1);

namespace App\Domain\Kontrak\Application\DTO;

final readonly class KontrakData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $PenyediaId = null,
        public mixed $Nomor = null,
        public mixed $Nama = null,
        public mixed $Jenis = null,
        public mixed $MulaiPada = null,
        public mixed $BerakhirPada = null,
        public mixed $Nilai = null,
        public mixed $MataUang = null,
        public mixed $TingkatLayananId = null,
        public mixed $PeringatanHariSebelum = null,
        public mixed $Status = null,
        public mixed $Catatan = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            PenyediaId: $data['PenyediaId'] ?? null,
            Nomor: $data['Nomor'] ?? null,
            Nama: $data['Nama'] ?? null,
            Jenis: $data['Jenis'] ?? null,
            MulaiPada: $data['MulaiPada'] ?? null,
            BerakhirPada: $data['BerakhirPada'] ?? null,
            Nilai: $data['Nilai'] ?? null,
            MataUang: $data['MataUang'] ?? null,
            TingkatLayananId: $data['TingkatLayananId'] ?? null,
            PeringatanHariSebelum: $data['PeringatanHariSebelum'] ?? null,
            Status: $data['Status'] ?? null,
            Catatan: $data['Catatan'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
