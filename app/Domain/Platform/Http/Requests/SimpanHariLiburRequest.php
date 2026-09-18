<?php

declare(strict_types=1);

namespace App\Domain\Platform\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanHariLiburRequest extends FormRequest
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
            'LokasiId' => ['nullable'],
            'Tanggal' => ['sometimes'],
            'Nama' => ['sometimes'],
            'BerulangTahunan' => ['sometimes'],
        ];
    }
}
