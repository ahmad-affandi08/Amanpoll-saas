<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Http\Requests;

use App\Domain\Pemeliharaan\Domain\Enums\StatusKeluhan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UbahStatusKeluhanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'Status' => ['required', Rule::enum(StatusKeluhan::class)],
            'Catatan' => [Rule::requiredIf(in_array($this->input('Status'), [StatusKeluhan::Ditolak->value, StatusKeluhan::Dibatalkan->value], true)), 'nullable', 'string', 'max:2000'],
            'Versi' => ['required', 'integer', 'min:1'],
        ];
    }
}
