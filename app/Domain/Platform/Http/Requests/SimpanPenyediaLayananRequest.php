<?php

declare(strict_types=1);

namespace App\Domain\Platform\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Setelan satu penyedia layanan dari konsol platform (PRD 8.23). */
final class SimpanPenyediaLayananRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'Aktif' => ['required', 'boolean'],
            'Utama' => ['sometimes', 'boolean'],
            'ModeUji' => ['sometimes', 'boolean'],
            'Kredensial' => ['sometimes', 'array'],
            'Kredensial.*' => ['nullable', 'string', 'max:4000'],
        ];
    }
}
