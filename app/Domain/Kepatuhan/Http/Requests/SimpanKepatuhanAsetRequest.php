<?php

declare(strict_types=1);

namespace App\Domain\Kepatuhan\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanKepatuhanAsetRequest extends FormRequest
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
            'AsetId' => ['sometimes'],
            'PersyaratanKepatuhanId' => ['sometimes'],
            'Status' => ['sometimes'],
            'TanggalPemeriksaan' => ['nullable'],
            'BerlakuSampai' => ['nullable'],
            'Catatan' => ['nullable'],
            'DiperiksaOleh' => ['nullable'],
        ];
    }
}
