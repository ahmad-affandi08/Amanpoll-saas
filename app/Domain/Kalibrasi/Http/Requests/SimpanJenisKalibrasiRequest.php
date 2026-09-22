<?php

declare(strict_types=1);

namespace App\Domain\Kalibrasi\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanJenisKalibrasiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'Kode' => ['nullable', 'string', 'max:60'],
            'Nama' => ['required', 'string', 'max:160'],
            'Deskripsi' => ['nullable', 'string'],
            'Aktif' => ['sometimes', 'boolean'],
        ];
    }
}
