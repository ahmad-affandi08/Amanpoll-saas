<?php

declare(strict_types=1);

namespace App\Domain\Platform\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanUnitOrganisasiRequest extends FormRequest
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
            'IndukId' => ['nullable'],
            'Kode' => ['sometimes'],
            'Nama' => ['sometimes'],
            'Jenis' => ['sometimes'],
            'Email' => ['nullable'],
            'Telepon' => ['nullable'],
            'Status' => ['sometimes'],
            'Urutan' => ['sometimes'],
        ];
    }
}
