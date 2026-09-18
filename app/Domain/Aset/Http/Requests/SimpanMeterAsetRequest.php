<?php

declare(strict_types=1);

namespace App\Domain\Aset\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanMeterAsetRequest extends FormRequest
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
            'Nama' => ['sometimes'],
            'Satuan' => ['sometimes'],
            'Jenis' => ['sometimes'],
            'NilaiAwal' => ['sometimes'],
            'Aktif' => ['sometimes'],
        ];
    }
}
