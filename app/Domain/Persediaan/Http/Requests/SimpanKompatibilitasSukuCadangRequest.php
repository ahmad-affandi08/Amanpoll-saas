<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanKompatibilitasSukuCadangRequest extends FormRequest
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
            'SukuCadangId' => ['sometimes'],
            'KategoriAsetId' => ['nullable'],
            'ModelAsetId' => ['nullable'],
            'AsetId' => ['nullable'],
            'Catatan' => ['nullable'],
        ];
    }
}
