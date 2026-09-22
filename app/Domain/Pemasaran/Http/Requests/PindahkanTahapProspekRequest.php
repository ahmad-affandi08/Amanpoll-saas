<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class PindahkanTahapProspekRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('platform') !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'Kode' => ['required', 'string', Rule::exists('TahapPipeline', 'Kode')],
            'Alasan' => ['nullable', 'string', 'max:255'],
        ];
    }
}
