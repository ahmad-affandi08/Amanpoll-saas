<?php

declare(strict_types=1);

namespace App\Domain\Langganan\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanPaketLanggananRequest extends FormRequest
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
            'Deskripsi' => ['nullable'],
            'HargaBulanan' => ['sometimes'],
            'HargaTahunan' => ['sometimes'],
            'MataUang' => ['sometimes'],
            'Aktif' => ['sometimes'],
        ];
    }
}
