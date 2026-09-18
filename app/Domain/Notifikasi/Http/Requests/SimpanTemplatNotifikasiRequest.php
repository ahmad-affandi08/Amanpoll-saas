<?php

declare(strict_types=1);

namespace App\Domain\Notifikasi\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanTemplatNotifikasiRequest extends FormRequest
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
            'Kode' => ['sometimes'],
            'Kanal' => ['sometimes'],
            'JudulTemplat' => ['nullable'],
            'IsiTemplat' => ['sometimes'],
            'Variabel' => ['nullable'],
            'Aktif' => ['sometimes'],
        ];
    }
}
