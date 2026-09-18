<?php

declare(strict_types=1);

namespace App\Domain\Platform\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanOrganisasiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        // Perketat rule sesuai invariant use-case sebelum endpoint diaktifkan.
        return [
            'Kode' => ['sometimes'],
            'Nama' => ['sometimes'],
            'NamaLegal' => ['nullable'],
            'JenisUsaha' => ['nullable'],
            'NomorIdentitasPajak' => ['nullable'],
            'Email' => ['nullable'],
            'Telepon' => ['nullable'],
            'Alamat' => ['nullable'],
            'Negara' => ['nullable'],
            'Provinsi' => ['nullable'],
            'Kota' => ['nullable'],
            'ZonaWaktu' => ['sometimes'],
            'LogoUrl' => ['nullable'],
            'Status' => ['sometimes'],
        ];
    }
}
