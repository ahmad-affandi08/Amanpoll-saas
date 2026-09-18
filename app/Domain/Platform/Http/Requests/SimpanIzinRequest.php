<?php

declare(strict_types=1);

namespace App\Domain\Platform\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanIzinRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        // Perketat rule sesuai invariant use-case sebelum endpoint diaktifkan.
        return [
            'Kode' => ['sometimes'],
            'Nama' => ['sometimes'],
            'Modul' => ['sometimes'],
            'Keterangan' => ['nullable'],
        ];
    }
}
