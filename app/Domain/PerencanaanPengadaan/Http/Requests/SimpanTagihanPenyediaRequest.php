<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanTagihanPenyediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'NomorTagihan' => ['required', 'string', 'max:100'],
            'TanggalTagihan' => ['required', 'date'],
            'JatuhTempo' => ['nullable', 'date', 'after_or_equal:TanggalTagihan'],
            'Subtotal' => ['required', 'numeric', 'decimal:0,2', 'gt:0'],
            'Pajak' => ['nullable', 'numeric', 'decimal:0,2', 'min:0'],
        ];
    }
}
