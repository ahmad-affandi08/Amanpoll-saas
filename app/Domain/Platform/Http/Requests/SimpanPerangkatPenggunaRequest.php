<?php

declare(strict_types=1);

namespace App\Domain\Platform\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanPerangkatPenggunaRequest extends FormRequest
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
            'PenggunaId' => ['sometimes'],
            'NamaPerangkat' => ['nullable'],
            'Platform' => ['nullable'],
            'IdentitasPerangkat' => ['nullable'],
            'TokenPush' => ['nullable'],
            'TerakhirSinkronPada' => ['nullable'],
            'Status' => ['sometimes'],
        ];
    }
}
