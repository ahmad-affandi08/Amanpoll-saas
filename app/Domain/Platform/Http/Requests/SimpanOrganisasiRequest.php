<?php

declare(strict_types=1);

namespace App\Domain\Platform\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SimpanOrganisasiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'Nama' => ['required', 'string', 'max:180'],
            'NamaLegal' => ['nullable', 'string', 'max:220'],
            'JenisUsaha' => ['nullable', 'string', 'max:100'],
            'NomorIdentitasPajak' => ['nullable', 'string', 'max:100'],
            'Email' => ['nullable', 'email', 'max:180'],
            'Telepon' => ['nullable', 'string', 'max:50'],
            'Alamat' => ['nullable', 'string'],
            'Negara' => ['nullable', 'string', 'max:100'],
            'Provinsi' => ['nullable', 'string', 'max:120'],
            'Kota' => ['nullable', 'string', 'max:120'],
            'ZonaWaktu' => ['required', 'string', Rule::in(timezone_identifiers_list())],
        ];
    }
}
