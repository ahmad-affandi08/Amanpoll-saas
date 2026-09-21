<?php

declare(strict_types=1);

namespace App\Domain\Kontrak\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CatatPemakaianLayananRequest extends FormRequest
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
            'Jumlah' => ['required', 'numeric', 'decimal:0,4', 'gt:0'],
        ];
    }
}
