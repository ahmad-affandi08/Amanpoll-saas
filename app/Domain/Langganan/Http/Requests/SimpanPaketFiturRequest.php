<?php

declare(strict_types=1);

namespace App\Domain\Langganan\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanPaketFiturRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        // Perketat rule sesuai invariant use-case sebelum endpoint diaktifkan.
        return [
            'PaketLanggananId' => ['sometimes'],
            'FiturPaketId' => ['sometimes'],
            'Diizinkan' => ['sometimes'],
            'BatasNilai' => ['nullable'],
            'NilaiJson' => ['nullable'],
        ];
    }
}
