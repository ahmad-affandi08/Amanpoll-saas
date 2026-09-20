<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ResponsPenugasanPerintahKerjaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'Respons' => ['required', Rule::in(['Terima', 'Tolak'])],
            'Catatan' => [Rule::requiredIf($this->input('Respons') === 'Tolak'), 'nullable', 'string', 'max:1000'],
        ];
    }
}
