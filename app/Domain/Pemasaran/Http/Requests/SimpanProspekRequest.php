<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanProspekRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('platform') !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'Nama' => ['required', 'string', 'max:190'],
            'Email' => ['nullable', 'email', 'max:190'],
            'Telepon' => ['nullable', 'string', 'max:60'],
            'WhatsApp' => ['nullable', 'string', 'max:60'],
            'Jabatan' => ['nullable', 'string', 'max:120'],
            'Perusahaan' => ['nullable', 'string', 'max:190'],
            'Industri' => ['nullable', 'string', 'max:120'],
            'JumlahLokasi' => ['nullable', 'integer', 'min:0'],
            'EstimasiAset' => ['nullable', 'integer', 'min:0'],
            'EstimasiTeknisi' => ['nullable', 'integer', 'min:0'],
            'Kota' => ['nullable', 'string', 'max:120'],
            'Negara' => ['nullable', 'string', 'max:120'],
            'Catatan' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
