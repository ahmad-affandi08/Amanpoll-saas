<?php

declare(strict_types=1);

namespace App\Domain\Aset\Application\DTO;

final readonly class AsetData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $UnitOrganisasiId = null,
        public mixed $LokasiId = null,
        public mixed $KategoriAsetId = null,
        public mixed $ModelAsetId = null,
        public mixed $PenyediaId = null,
        public mixed $KodeAset = null,
        public mixed $Nama = null,
        public mixed $NomorSeri = null,
        public mixed $NomorInventaris = null,
        public mixed $NomorRegistrasiEksternal = null,
        public mixed $TanggalPerolehan = null,
        public mixed $TanggalMulaiOperasi = null,
        public mixed $TanggalAkhirOperasi = null,
        public mixed $HargaPerolehan = null,
        public mixed $NilaiResidu = null,
        public mixed $MataUang = null,
        public mixed $SumberDana = null,
        public mixed $MetodePenyusutan = null,
        public mixed $UmurManfaatBulan = null,
        public mixed $Status = null,
        public mixed $Kondisi = null,
        public mixed $TingkatKritis = null,
        public mixed $KodeQr = null,
        public mixed $NfcUid = null,
        public mixed $KodeBatang = null,
        public mixed $Catatan = null,
        public mixed $Versi = null,
        public mixed $DibuatOleh = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            UnitOrganisasiId: $data['UnitOrganisasiId'] ?? null,
            LokasiId: $data['LokasiId'] ?? null,
            KategoriAsetId: $data['KategoriAsetId'] ?? null,
            ModelAsetId: $data['ModelAsetId'] ?? null,
            PenyediaId: $data['PenyediaId'] ?? null,
            KodeAset: $data['KodeAset'] ?? null,
            Nama: $data['Nama'] ?? null,
            NomorSeri: $data['NomorSeri'] ?? null,
            NomorInventaris: $data['NomorInventaris'] ?? null,
            NomorRegistrasiEksternal: $data['NomorRegistrasiEksternal'] ?? null,
            TanggalPerolehan: $data['TanggalPerolehan'] ?? null,
            TanggalMulaiOperasi: $data['TanggalMulaiOperasi'] ?? null,
            TanggalAkhirOperasi: $data['TanggalAkhirOperasi'] ?? null,
            HargaPerolehan: $data['HargaPerolehan'] ?? null,
            NilaiResidu: $data['NilaiResidu'] ?? null,
            MataUang: $data['MataUang'] ?? null,
            SumberDana: $data['SumberDana'] ?? null,
            MetodePenyusutan: $data['MetodePenyusutan'] ?? null,
            UmurManfaatBulan: $data['UmurManfaatBulan'] ?? null,
            Status: $data['Status'] ?? null,
            Kondisi: $data['Kondisi'] ?? null,
            TingkatKritis: $data['TingkatKritis'] ?? null,
            KodeQr: $data['KodeQr'] ?? null,
            NfcUid: $data['NfcUid'] ?? null,
            KodeBatang: $data['KodeBatang'] ?? null,
            Catatan: $data['Catatan'] ?? null,
            Versi: $data['Versi'] ?? null,
            DibuatOleh: $data['DibuatOleh'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
