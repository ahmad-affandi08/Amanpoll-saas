<?php

declare(strict_types=1);

namespace App\Domain\Platform\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanKunciApiRequest extends FormRequest
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
            'Nama' => ['sometimes'],
            'AwalanKunci' => ['sometimes'],
            'HashKunci' => ['sometimes'],
            'Cakupan' => ['nullable'],
            'AlamatIpDiizinkan' => ['nullable'],
            'KadaluarsaPada' => ['nullable'],
            'TerakhirDipakaiPada' => ['nullable'],
            'Status' => ['sometimes'],
            'DibuatOleh' => ['nullable'],
        ];
    }
}
