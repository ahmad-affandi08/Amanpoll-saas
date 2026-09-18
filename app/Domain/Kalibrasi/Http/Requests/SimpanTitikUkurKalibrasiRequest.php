<?php

declare(strict_types=1);

namespace App\Domain\Kalibrasi\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanTitikUkurKalibrasiRequest extends FormRequest
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
            'JenisKalibrasiId' => ['nullable'],
            'KategoriAsetId' => ['nullable'],
            'Nama' => ['sometimes'],
            'Satuan' => ['nullable'],
            'NilaiReferensi' => ['nullable'],
            'ToleransiMinus' => ['nullable'],
            'ToleransiPlus' => ['nullable'],
            'Urutan' => ['sometimes'],
            'Aktif' => ['sometimes'],
        ];
    }
}
