<?php

declare(strict_types=1);

namespace App\Domain\Aset\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanModelAsetRequest extends FormRequest
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
            'KategoriAsetId' => ['sometimes'],
            'MerekId' => ['nullable'],
            'KodeModel' => ['nullable'],
            'Nama' => ['sometimes'],
            'Produsen' => ['nullable'],
            'Spesifikasi' => ['nullable'],
            'IntervalPemeliharaanHari' => ['nullable'],
            'IntervalKalibrasiHari' => ['nullable'],
            'UmurManfaatBulan' => ['nullable'],
        ];
    }
}
