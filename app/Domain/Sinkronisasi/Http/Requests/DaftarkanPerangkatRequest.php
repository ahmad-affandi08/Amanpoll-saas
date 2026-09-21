<?php

declare(strict_types=1);

namespace App\Domain\Sinkronisasi\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class DaftarkanPerangkatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'IdentitasPerangkat' => ['required', 'string', 'max:255'],
            'NamaPerangkat' => ['nullable', 'string', 'max:180'],
            'Platform' => ['nullable', 'string', 'max:60'],
        ];
    }
}
