<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Http\Requests;

use App\Domain\Pemeliharaan\Domain\Enums\PrioritasKeluhan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UbahPrioritasKeluhanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'Prioritas' => ['required', Rule::enum(PrioritasKeluhan::class)],
            'Alasan' => ['required', 'string', 'max:2000'],
            'Versi' => ['required', 'integer', 'min:1'],
        ];
    }
}
