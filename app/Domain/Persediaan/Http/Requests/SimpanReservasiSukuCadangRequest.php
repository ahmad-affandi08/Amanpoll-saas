<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanReservasiSukuCadangRequest extends FormRequest
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
            'PerintahKerjaId' => ['nullable'],
            'GudangId' => ['sometimes'],
            'SukuCadangId' => ['sometimes'],
            'Jumlah' => ['sometimes'],
            'Status' => ['sometimes'],
            'KadaluarsaPada' => ['nullable'],
            'DibuatOleh' => ['nullable'],
        ];
    }
}
