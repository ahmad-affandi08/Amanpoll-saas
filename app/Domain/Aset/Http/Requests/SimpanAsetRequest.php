<?php

declare(strict_types=1);

namespace App\Domain\Aset\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanAsetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        // Perketat rule sesuai invariant use-case sebelum endpoint diaktifkan.
        return [
            'OrganisasiId' => ['sometimes'],
            'UnitOrganisasiId' => ['nullable'],
            'LokasiId' => ['nullable'],
            'KategoriAsetId' => ['sometimes'],
            'ModelAsetId' => ['nullable'],
            'PenyediaId' => ['nullable'],
            'KodeAset' => ['sometimes'],
            'Nama' => ['sometimes'],
            'NomorSeri' => ['nullable'],
            'NomorInventaris' => ['nullable'],
            'NomorRegistrasiEksternal' => ['nullable'],
            'TanggalPerolehan' => ['nullable'],
            'TanggalMulaiOperasi' => ['nullable'],
            'TanggalAkhirOperasi' => ['nullable'],
            'HargaPerolehan' => ['nullable'],
            'NilaiResidu' => ['nullable'],
            'MataUang' => ['sometimes'],
            'SumberDana' => ['nullable'],
            'MetodePenyusutan' => ['nullable'],
            'UmurManfaatBulan' => ['nullable'],
            'Status' => ['sometimes'],
            'Kondisi' => ['sometimes'],
            'TingkatKritis' => ['sometimes'],
            'KodeQr' => ['nullable'],
            'NfcUid' => ['nullable'],
            'KodeBatang' => ['nullable'],
            'Catatan' => ['nullable'],
            'Versi' => ['sometimes'],
            'DibuatOleh' => ['nullable'],
        ];
    }
}
