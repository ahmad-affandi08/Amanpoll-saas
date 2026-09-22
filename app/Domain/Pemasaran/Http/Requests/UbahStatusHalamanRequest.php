<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Http\Requests;

use App\Domain\Pemasaran\Domain\Enums\StatusHalamanPemasaran;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UbahStatusHalamanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('platform') !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'Status' => [
                'required',
                Rule::enum(StatusHalamanPemasaran::class)
                    // Terbit punya rutenya sendiri karena ia juga memindahkan
                    // versi terbit dan menulis audit penerbitan.
                    ->except([StatusHalamanPemasaran::Terbit]),
            ],
            'TerbitPada' => [
                Rule::requiredIf(fn (): bool => $this->input('Status') === StatusHalamanPemasaran::Terjadwal->value),
                'nullable',
                'date',
            ],
            'TarikPada' => ['nullable', 'date', 'after:TerbitPada'],
        ];
    }
}
