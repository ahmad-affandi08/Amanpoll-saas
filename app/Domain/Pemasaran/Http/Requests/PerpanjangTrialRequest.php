<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class PerpanjangTrialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('platform') !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            // Batas atasnya ditegakkan aksi, yang membaca extension policy.
            'Hari' => ['required', 'integer', 'min:1', 'max:365'],
            'Alasan' => ['nullable', 'string', 'max:500'],
        ];
    }
}
