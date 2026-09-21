<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanPembayaranPenyediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'NomorPembayaran' => ['required', 'string', 'max:100'],
            'TanggalBayar' => ['required', 'date'],
            'Jumlah' => ['required', 'numeric', 'decimal:0,2', 'gt:0'],
            'Metode' => ['required', 'string', 'max:60'],
            'Referensi' => ['nullable', 'string', 'max:160'],
        ];
    }
}
