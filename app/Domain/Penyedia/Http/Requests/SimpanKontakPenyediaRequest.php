<?php

declare(strict_types=1);

namespace App\Domain\Penyedia\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanKontakPenyediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'Nama' => ['required', 'string', 'max:180'],
            'Jabatan' => ['nullable', 'string', 'max:120'],
            'Email' => ['nullable', 'email', 'max:180'],
            'Telepon' => ['nullable', 'string', 'max:60'],
            'Utama' => ['boolean'],
        ];
    }
}
