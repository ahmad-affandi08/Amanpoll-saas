<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class AksiWaktuKerjaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'Aksi' => ['required', Rule::in(['Mulai', 'Jeda', 'Lanjut', 'Selesai'])],
            'Catatan' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
