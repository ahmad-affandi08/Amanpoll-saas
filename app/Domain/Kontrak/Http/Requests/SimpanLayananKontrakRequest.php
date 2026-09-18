<?php

declare(strict_types=1);

namespace App\Domain\Kontrak\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanLayananKontrakRequest extends FormRequest
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
            'Nama' => ['sometimes'],
            'Deskripsi' => ['nullable'],
            'Kuota' => ['nullable'],
            'Satuan' => ['nullable'],
            'Terpakai' => ['sometimes'],
        ];
    }
}
