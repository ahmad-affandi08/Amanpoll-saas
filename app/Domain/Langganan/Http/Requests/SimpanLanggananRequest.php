<?php

declare(strict_types=1);

namespace App\Domain\Langganan\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanLanggananRequest extends FormRequest
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
            'PaketLanggananId' => ['sometimes'],
            'Siklus' => ['sometimes'],
            'MulaiPada' => ['sometimes'],
            'BerakhirPada' => ['nullable'],
            'UjiCobaSampai' => ['nullable'],
            'Status' => ['sometimes'],
            'BatalPada' => ['nullable'],
        ];
    }
}
