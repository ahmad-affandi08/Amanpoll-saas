<?php

declare(strict_types=1);

namespace App\Domain\Penyedia\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanPenilaianPenyediaRequest extends FormRequest
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
            'PeriodeMulai' => ['required', 'date'],
            'PeriodeSelesai' => ['required', 'date', 'after_or_equal:PeriodeMulai'],
            'SkorKualitas' => ['nullable', 'numeric', 'between:0,100'],
            'SkorKetepatanWaktu' => ['nullable', 'numeric', 'between:0,100'],
            'SkorHarga' => ['nullable', 'numeric', 'between:0,100'],
            'SkorLayanan' => ['nullable', 'numeric', 'between:0,100'],
            'Catatan' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
