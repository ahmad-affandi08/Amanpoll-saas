<?php

declare(strict_types=1);

namespace App\Domain\Pelaporan\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanKomponenDasborRequest extends FormRequest
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
            'DasborTersimpanId' => ['sometimes'],
            'JenisKomponen' => ['sometimes'],
            'Judul' => ['nullable'],
            'Konfigurasi' => ['sometimes'],
            'PosisiX' => ['sometimes'],
            'PosisiY' => ['sometimes'],
            'Lebar' => ['sometimes'],
            'Tinggi' => ['sometimes'],
            'Urutan' => ['sometimes'],
        ];
    }
}
