<?php

declare(strict_types=1);

namespace App\Domain\IntegrasiAudit\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanPengirimanPanggilanBalikWebRequest extends FormRequest
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
            'PanggilanBalikWebId' => ['sometimes'],
            'Peristiwa' => ['sometimes'],
            'MuatanData' => ['sometimes'],
            'StatusHttp' => ['nullable'],
            'Respons' => ['nullable'],
            'Status' => ['sometimes'],
            'Percobaan' => ['sometimes'],
            'JadwalCobaLagiPada' => ['nullable'],
            'DikirimPada' => ['nullable'],
        ];
    }
}
