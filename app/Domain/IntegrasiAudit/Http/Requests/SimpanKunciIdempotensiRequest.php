<?php

declare(strict_types=1);

namespace App\Domain\IntegrasiAudit\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanKunciIdempotensiRequest extends FormRequest
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
            'Kunci' => ['sometimes'],
            'Rute' => ['sometimes'],
            'HashPermintaan' => ['nullable'],
            'StatusHttp' => ['nullable'],
            'Respons' => ['nullable'],
            'KadaluarsaPada' => ['sometimes'],
        ];
    }
}
