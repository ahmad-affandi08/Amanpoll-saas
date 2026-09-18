<?php

declare(strict_types=1);

namespace App\Domain\Kepatuhan\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanIntegrasiEksternalRequest extends FormRequest
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
            'Kode' => ['sometimes'],
            'Nama' => ['sometimes'],
            'Jenis' => ['sometimes'],
            'UrlDasar' => ['nullable'],
            'MetodeAutentikasi' => ['nullable'],
            'KonfigurasiTerenkripsi' => ['nullable'],
            'Status' => ['sometimes'],
            'TerakhirSinkronPada' => ['nullable'],
        ];
    }
}
