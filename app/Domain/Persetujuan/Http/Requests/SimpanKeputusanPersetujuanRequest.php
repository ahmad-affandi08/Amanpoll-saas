<?php

declare(strict_types=1);

namespace App\Domain\Persetujuan\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanKeputusanPersetujuanRequest extends FormRequest
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
            'Catatan' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
