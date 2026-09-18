<?php

declare(strict_types=1);

namespace App\Domain\Aset\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanGaransiAsetRequest extends FormRequest
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
            'AsetId' => ['sometimes'],
            'PenyediaId' => ['nullable'],
            'NomorGaransi' => ['nullable'],
            'JenisGaransi' => ['nullable'],
            'MulaiPada' => ['sometimes'],
            'BerakhirPada' => ['sometimes'],
            'Cakupan' => ['nullable'],
            'Status' => ['sometimes'],
        ];
    }
}
