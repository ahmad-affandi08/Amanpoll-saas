<?php

declare(strict_types=1);

namespace App\Domain\Sinkronisasi\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanPenandaSinkronisasiRequest extends FormRequest
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
            'PerangkatPenggunaId' => ['sometimes'],
            'JenisEntitas' => ['sometimes'],
            'TokenSinkronisasi' => ['nullable'],
            'TerakhirSinkronPada' => ['nullable'],
        ];
    }
}
