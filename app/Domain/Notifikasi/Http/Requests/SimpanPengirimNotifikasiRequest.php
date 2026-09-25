<?php

declare(strict_types=1);

namespace App\Domain\Notifikasi\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Setelan email atau WhatsApp milik organisasi (PRD 8.23). */
final class SimpanPengirimNotifikasiRequest extends FormRequest
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
            'ModeUji' => ['sometimes', 'boolean'],
            'Kredensial' => ['sometimes', 'array'],
            'Kredensial.*' => ['nullable', 'string', 'max:4000'],
        ];
    }
}
