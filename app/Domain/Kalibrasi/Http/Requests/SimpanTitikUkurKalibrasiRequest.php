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
        return [
            'JenisKalibrasiId' => ['nullable', 'string', 'max:26'],
            'KategoriAsetId' => ['nullable', 'string', 'max:26'],
            'Nama' => ['required', 'string', 'max:160'],
            'Satuan' => ['nullable', 'string', 'max:50'],
            'NilaiReferensi' => ['nullable', 'numeric'],
            'ToleransiMinus' => ['nullable', 'numeric', 'min:0'],
            'ToleransiPlus' => ['nullable', 'numeric', 'min:0'],
            'Urutan' => ['nullable', 'integer'],
            'Aktif' => ['sometimes', 'boolean'],
        ];
    }
}
