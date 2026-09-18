<?php

declare(strict_types=1);

namespace App\Domain\IntegrasiAudit\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanPanggilanBalikWebRequest extends FormRequest
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
            'Url' => ['sometimes'],
            'Rahasia' => ['nullable'],
            'Peristiwa' => ['sometimes'],
            'Aktif' => ['sometimes'],
        ];
    }
}
