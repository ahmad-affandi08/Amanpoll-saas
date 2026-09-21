<?php

declare(strict_types=1);

namespace App\Domain\Kepatuhan\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanPersyaratanKepatuhanRequest extends FormRequest
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
            'Kode' => ['required', 'string', 'max:100'],
            'Nama' => ['required', 'string', 'max:220'],
            'Deskripsi' => ['nullable', 'string', 'max:5000'],
            'BuktiYangDiperlukan' => ['nullable', 'string', 'max:2000'],
            'IntervalHari' => ['nullable', 'integer', 'min:1', 'max:3650'],
        ];
    }
}
