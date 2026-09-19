<?php

declare(strict_types=1);

namespace App\Domain\Aset\Http\Resources;

use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class AsetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Aset $aset */
        $aset = $this->resource;

        return [
            'Id' => $aset->Id,
            'OrganisasiId' => $aset->OrganisasiId,
            'UnitOrganisasiId' => $aset->UnitOrganisasiId,
            'NamaUnitOrganisasi' => $this->whenLoaded('unitOrganisasi', fn () => $aset->unitOrganisasi?->Nama),
            'LokasiId' => $aset->LokasiId,
            'NamaLokasi' => $this->whenLoaded('lokasi', fn () => $aset->lokasi?->Nama),
            'KategoriAsetId' => $aset->KategoriAsetId,
            'NamaKategoriAset' => $this->whenLoaded('kategoriAset', fn () => $aset->kategoriAset?->Nama),
            'ModelAsetId' => $aset->ModelAsetId,
            'NamaModelAset' => $this->whenLoaded('modelAset', fn () => $aset->modelAset?->Nama),
            'PenyediaId' => $aset->PenyediaId,
            'NamaPenyedia' => $this->whenLoaded('penyedia', fn () => $aset->penyedia?->Nama),
            'KodeAset' => $aset->KodeAset,
            'Nama' => $aset->Nama,
            'NomorSeri' => $aset->NomorSeri,
            'NomorInventaris' => $aset->NomorInventaris,
            'NomorRegistrasiEksternal' => $aset->NomorRegistrasiEksternal,
            'TanggalPerolehan' => $aset->TanggalPerolehan?->toDateString(),
            'TanggalMulaiOperasi' => $aset->TanggalMulaiOperasi?->toDateString(),
            'TanggalAkhirOperasi' => $aset->TanggalAkhirOperasi?->toDateString(),
            'HargaPerolehan' => $aset->HargaPerolehan,
            'NilaiResidu' => $aset->NilaiResidu,
            'MataUang' => $aset->MataUang,
            'SumberDana' => $aset->SumberDana,
            'MetodePenyusutan' => $aset->MetodePenyusutan,
            'UmurManfaatBulan' => $aset->UmurManfaatBulan,
            'Status' => $aset->Status,
            'Kondisi' => $aset->Kondisi,
            'TingkatKritis' => $aset->TingkatKritis,
            'KodeQr' => $aset->KodeQr,
            'NfcUid' => $aset->NfcUid,
            'KodeBatang' => $aset->KodeBatang,
            'Catatan' => $aset->Catatan,
            'Versi' => $aset->Versi,
            'NamaDibuatOleh' => $this->whenLoaded('dibuatOleh', fn () => $aset->dibuatOleh?->Nama),
            'DibuatPada' => $aset->DibuatPada->toIso8601String(),
        ];
    }
}
