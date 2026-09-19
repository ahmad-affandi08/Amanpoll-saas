<?php

declare(strict_types=1);

namespace App\Domain\Aset\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanPembacaanMeterAsetRequest extends FormRequest
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
            'Nilai' => ['required', 'numeric', 'min:0'],
            'DibacaPada' => ['required', 'date'],
            'Sumber' => ['nullable', 'string', 'max:40'],
        ];
    }
}
