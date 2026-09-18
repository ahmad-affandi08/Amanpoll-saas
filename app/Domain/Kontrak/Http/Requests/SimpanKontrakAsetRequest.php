<?php

declare(strict_types=1);

namespace App\Domain\Kontrak\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanKontrakAsetRequest extends FormRequest
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
            'KontrakId' => ['sometimes'],
            'AsetId' => ['sometimes'],
            'MulaiPada' => ['nullable'],
            'BerakhirPada' => ['nullable'],
            'Catatan' => ['nullable'],
        ];
    }
}
