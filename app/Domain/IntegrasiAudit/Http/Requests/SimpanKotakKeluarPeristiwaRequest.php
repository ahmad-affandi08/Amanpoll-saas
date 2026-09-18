<?php

declare(strict_types=1);

namespace App\Domain\IntegrasiAudit\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanKotakKeluarPeristiwaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        // Perketat rule sesuai invariant use-case sebelum endpoint diaktifkan.
        return [
            'OrganisasiId' => ['nullable'],
            'NamaPeristiwa' => ['sometimes'],
            'JenisAgregat' => ['nullable'],
            'AgregatId' => ['nullable'],
            'MuatanData' => ['sometimes'],
            'Status' => ['sometimes'],
            'Percobaan' => ['sometimes'],
            'TersediaPada' => ['sometimes'],
            'DiprosesPada' => ['nullable'],
            'KesalahanTerakhir' => ['nullable'],
        ];
    }
}
