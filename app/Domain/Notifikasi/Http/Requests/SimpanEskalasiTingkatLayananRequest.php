<?php

declare(strict_types=1);

namespace App\Domain\Notifikasi\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanEskalasiTingkatLayananRequest extends FormRequest
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
            'TingkatLayananId' => ['sometimes'],
            'Tahap' => ['sometimes'],
            'SetelahMenit' => ['sometimes'],
            'PeranId' => ['nullable'],
            'PenggunaId' => ['nullable'],
            'Kanal' => ['nullable'],
            'Aktif' => ['sometimes'],
        ];
    }
}
