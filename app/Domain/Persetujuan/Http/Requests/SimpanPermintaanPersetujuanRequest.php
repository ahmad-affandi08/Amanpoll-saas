<?php

declare(strict_types=1);

namespace App\Domain\Persetujuan\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanPermintaanPersetujuanRequest extends FormRequest
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
            'AlurPersetujuanId' => ['sometimes'],
            'JenisEntitas' => ['sometimes'],
            'EntitasId' => ['sometimes'],
            'TahapSaatIni' => ['sometimes'],
            'Status' => ['sometimes'],
            'DimintaOleh' => ['sometimes'],
            'DimintaPada' => ['sometimes'],
            'SelesaiPada' => ['nullable'],
            'DataTambahan' => ['nullable'],
        ];
    }
}
