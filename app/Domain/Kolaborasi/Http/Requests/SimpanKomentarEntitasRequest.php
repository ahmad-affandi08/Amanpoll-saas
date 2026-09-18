<?php

declare(strict_types=1);

namespace App\Domain\Kolaborasi\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanKomentarEntitasRequest extends FormRequest
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
            'JenisEntitas' => ['sometimes'],
            'EntitasId' => ['sometimes'],
            'IndukKomentarId' => ['nullable'],
            'Isi' => ['sometimes'],
            'DibuatOleh' => ['sometimes'],
        ];
    }
}
