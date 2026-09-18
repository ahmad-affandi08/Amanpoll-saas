<?php

declare(strict_types=1);

namespace App\Domain\SiklusAset\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanDetailPenghapusanAsetRequest extends FormRequest
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
            'PengajuanPenghapusanAsetId' => ['sometimes'],
            'AsetId' => ['sometimes'],
            'NilaiBukuSaatPenghapusan' => ['nullable'],
            'HasilPelepasan' => ['nullable'],
            'Status' => ['sometimes'],
            'Catatan' => ['nullable'],
        ];
    }
}
