<?php

declare(strict_types=1);

namespace App\Domain\Platform\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanLokasiRequest extends FormRequest
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
            'UnitOrganisasiId' => ['nullable'],
            'KategoriLokasiId' => ['nullable'],
            'IndukId' => ['nullable'],
            'Kode' => ['sometimes'],
            'Nama' => ['sometimes'],
            'Alamat' => ['nullable'],
            'Lantai' => ['nullable'],
            'Latitude' => ['nullable'],
            'Longitude' => ['nullable'],
            'ZonaWaktu' => ['nullable'],
            'Status' => ['sometimes'],
        ];
    }
}
