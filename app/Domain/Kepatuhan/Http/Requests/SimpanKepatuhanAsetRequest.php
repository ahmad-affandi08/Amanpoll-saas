<?php

declare(strict_types=1);

namespace App\Domain\Kepatuhan\Http\Requests;

use App\Domain\Kepatuhan\Infrastructure\Persistence\Models\KepatuhanAset;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SimpanKepatuhanAsetRequest extends FormRequest
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
            'Status' => ['required', Rule::in([KepatuhanAset::STATUS_PATUH, KepatuhanAset::STATUS_TIDAK_PATUH])],
            'TanggalPemeriksaan' => ['required', 'date'],
            'BerlakuSampai' => ['nullable', 'date', 'after_or_equal:TanggalPemeriksaan'],
            'Catatan' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
