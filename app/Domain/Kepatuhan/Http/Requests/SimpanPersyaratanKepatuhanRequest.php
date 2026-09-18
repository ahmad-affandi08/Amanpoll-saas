<?php

declare(strict_types=1);

namespace App\Domain\Kepatuhan\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanPersyaratanKepatuhanRequest extends FormRequest
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
            'StandarKepatuhanId' => ['sometimes'],
            'Kode' => ['sometimes'],
            'Nama' => ['sometimes'],
            'Deskripsi' => ['nullable'],
            'BuktiYangDiperlukan' => ['nullable'],
            'IntervalHari' => ['nullable'],
        ];
    }
}
