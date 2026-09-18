<?php

declare(strict_types=1);

namespace App\Domain\Penyedia\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanPenyediaRequest extends FormRequest
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
            'Kode' => ['sometimes'],
            'Nama' => ['sometimes'],
            'NamaLegal' => ['nullable'],
            'NomorIdentitasPajak' => ['nullable'],
            'Email' => ['nullable'],
            'Telepon' => ['nullable'],
            'Website' => ['nullable'],
            'Alamat' => ['nullable'],
            'Kota' => ['nullable'],
            'Provinsi' => ['nullable'],
            'Negara' => ['nullable'],
            'Status' => ['sometimes'],
        ];
    }
}
