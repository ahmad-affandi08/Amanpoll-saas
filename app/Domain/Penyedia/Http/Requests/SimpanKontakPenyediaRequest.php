<?php

declare(strict_types=1);

namespace App\Domain\Penyedia\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanKontakPenyediaRequest extends FormRequest
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
            'PenyediaId' => ['sometimes'],
            'Nama' => ['sometimes'],
            'Jabatan' => ['nullable'],
            'Email' => ['nullable'],
            'Telepon' => ['nullable'],
            'Utama' => ['sometimes'],
        ];
    }
}
