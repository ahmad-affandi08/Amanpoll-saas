<?php

declare(strict_types=1);

namespace App\Domain\Kalibrasi\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanHasilTitikUkurKalibrasiRequest extends FormRequest
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
            'PelaksanaanKalibrasiId' => ['sometimes'],
            'TitikUkurKalibrasiId' => ['nullable'],
            'NamaTitik' => ['nullable'],
            'NilaiReferensi' => ['nullable'],
            'NilaiTerukur' => ['nullable'],
            'Koreksi' => ['nullable'],
            'Ketidakpastian' => ['nullable'],
            'Satuan' => ['nullable'],
            'Hasil' => ['nullable'],
            'Catatan' => ['nullable'],
        ];
    }
}
