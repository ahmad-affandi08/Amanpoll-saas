<?php

declare(strict_types=1);

namespace App\Domain\Kolaborasi\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanEntitasTagRequest extends FormRequest
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
            'TagId' => ['required', 'string'],
            'JenisEntitas' => ['required', 'string'],
            'EntitasId' => ['required', 'string'],
        ];
    }
}
