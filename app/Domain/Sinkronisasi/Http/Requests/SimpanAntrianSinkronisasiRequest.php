<?php

declare(strict_types=1);

namespace App\Domain\Sinkronisasi\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanAntrianSinkronisasiRequest extends FormRequest
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
            'KunciOperasi' => ['sometimes'],
            'JenisEntitas' => ['sometimes'],
            'EntitasId' => ['nullable'],
            'Operasi' => ['sometimes'],
            'VersiKlien' => ['nullable'],
            'MuatanData' => ['sometimes'],
            'Status' => ['sometimes'],
            'Konflik' => ['nullable'],
            'Percobaan' => ['sometimes'],
            'DiterimaPada' => ['sometimes'],
            'DiprosesPada' => ['nullable'],
        ];
    }
}
