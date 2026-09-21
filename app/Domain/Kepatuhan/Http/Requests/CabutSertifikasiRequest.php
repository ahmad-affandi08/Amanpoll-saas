<?php

declare(strict_types=1);

namespace App\Domain\Kepatuhan\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CabutSertifikasiRequest extends FormRequest
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
            'Alasan' => ['required', 'string', 'min:5', 'max:500'],
        ];
    }
}
