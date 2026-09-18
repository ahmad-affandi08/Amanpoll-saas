<?php

declare(strict_types=1);

namespace App\Domain\Kepatuhan\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanSinkronisasiEksternalRequest extends FormRequest
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
            'IntegrasiEksternalId' => ['sometimes'],
            'JenisProses' => ['sometimes'],
            'Arah' => ['sometimes'],
            'Status' => ['sometimes'],
            'JumlahData' => ['sometimes'],
            'JumlahBerhasil' => ['sometimes'],
            'JumlahGagal' => ['sometimes'],
            'PesanKesalahan' => ['nullable'],
            'MulaiPada' => ['sometimes'],
            'SelesaiPada' => ['nullable'],
        ];
    }
}
