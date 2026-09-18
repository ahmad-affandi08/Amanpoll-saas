<?php

declare(strict_types=1);

namespace App\Domain\SiklusAset\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanPermintaanMutasiAsetRequest extends FormRequest
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
            'Nomor' => ['sometimes'],
            'JenisMutasi' => ['sometimes'],
            'UnitAsalId' => ['nullable'],
            'UnitTujuanId' => ['nullable'],
            'LokasiAsalId' => ['nullable'],
            'LokasiTujuanId' => ['nullable'],
            'Alasan' => ['nullable'],
            'Status' => ['sometimes'],
            'DimintaOleh' => ['sometimes'],
            'DimintaPada' => ['sometimes'],
            'DisetujuiPada' => ['nullable'],
            'SelesaiPada' => ['nullable'],
            'Versi' => ['sometimes'],
        ];
    }
}
