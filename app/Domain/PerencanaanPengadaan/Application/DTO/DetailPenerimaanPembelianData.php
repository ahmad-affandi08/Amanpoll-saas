<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Application\DTO;

final readonly class DetailPenerimaanPembelianData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $PenerimaanPembelianId = null,
        public mixed $DetailPesananPembelianId = null,
        public mixed $SukuCadangId = null,
        public mixed $JumlahDipesan = null,
        public mixed $JumlahDiterima = null,
        public mixed $JumlahDitolak = null,
        public mixed $Kondisi = null,
        public mixed $NomorSeriJson = null,
        public mixed $Catatan = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            PenerimaanPembelianId: $data['PenerimaanPembelianId'] ?? null,
            DetailPesananPembelianId: $data['DetailPesananPembelianId'] ?? null,
            SukuCadangId: $data['SukuCadangId'] ?? null,
            JumlahDipesan: $data['JumlahDipesan'] ?? null,
            JumlahDiterima: $data['JumlahDiterima'] ?? null,
            JumlahDitolak: $data['JumlahDitolak'] ?? null,
            Kondisi: $data['Kondisi'] ?? null,
            NomorSeriJson: $data['NomorSeriJson'] ?? null,
            Catatan: $data['Catatan'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
