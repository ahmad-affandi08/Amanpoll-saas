<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Application\DTO;

final readonly class PesananPembelianData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $Nomor = null,
        public mixed $PenyediaId = null,
        public mixed $PermintaanPembelianId = null,
        public mixed $PenawaranPenyediaId = null,
        public mixed $PosAnggaranId = null,
        public mixed $TanggalPesanan = null,
        public mixed $TanggalKirimRencana = null,
        public mixed $MataUang = null,
        public mixed $Subtotal = null,
        public mixed $Pajak = null,
        public mixed $Diskon = null,
        public mixed $Total = null,
        public mixed $Status = null,
        public mixed $Catatan = null,
        public mixed $DibuatOleh = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            Nomor: $data['Nomor'] ?? null,
            PenyediaId: $data['PenyediaId'] ?? null,
            PermintaanPembelianId: $data['PermintaanPembelianId'] ?? null,
            PenawaranPenyediaId: $data['PenawaranPenyediaId'] ?? null,
            PosAnggaranId: $data['PosAnggaranId'] ?? null,
            TanggalPesanan: $data['TanggalPesanan'] ?? null,
            TanggalKirimRencana: $data['TanggalKirimRencana'] ?? null,
            MataUang: $data['MataUang'] ?? null,
            Subtotal: $data['Subtotal'] ?? null,
            Pajak: $data['Pajak'] ?? null,
            Diskon: $data['Diskon'] ?? null,
            Total: $data['Total'] ?? null,
            Status: $data['Status'] ?? null,
            Catatan: $data['Catatan'] ?? null,
            DibuatOleh: $data['DibuatOleh'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
