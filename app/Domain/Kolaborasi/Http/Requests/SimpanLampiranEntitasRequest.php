<?php

declare(strict_types=1);

namespace App\Domain\Kolaborasi\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanLampiranEntitasRequest extends FormRequest
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
            'JenisEntitas' => ['required', 'string'],
            'EntitasId' => ['required', 'string'],
            'BerkasId' => ['required', 'string'],
            'Kategori' => ['nullable', 'string', 'max:80'],
            'Keterangan' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
