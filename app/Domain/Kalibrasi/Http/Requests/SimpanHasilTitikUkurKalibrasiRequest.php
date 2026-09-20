<?php

declare(strict_types=1);

namespace App\Domain\Kalibrasi\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanHasilTitikUkurKalibrasiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'hasil' => ['required', 'array'],
            'hasil.*.Id' => ['nullable', 'string', 'max:26'],
            'hasil.*.TitikUkurKalibrasiId' => ['nullable', 'string', 'max:26'],
            'hasil.*.NamaTitik' => ['required', 'string', 'max:160'],
            'hasil.*.NilaiReferensi' => ['nullable', 'numeric'],
            'hasil.*.NilaiTerukur' => ['nullable', 'numeric'],
            'hasil.*.ToleransiMinus' => ['nullable', 'numeric'],
            'hasil.*.ToleransiPlus' => ['nullable', 'numeric'],
            'hasil.*.Ketidakpastian' => ['nullable', 'numeric'],
            'hasil.*.Satuan' => ['nullable', 'string', 'max:50'],
            'hasil.*.Hasil' => ['nullable', 'string', 'max:40'],
            'hasil.*.Catatan' => ['nullable', 'string'],
        ];
    }
}
