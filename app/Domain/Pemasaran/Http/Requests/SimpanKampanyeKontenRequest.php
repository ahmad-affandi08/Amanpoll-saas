<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Http\Requests;

use App\Domain\Pemasaran\Domain\Enums\JenisKontenKampanye;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SimpanKampanyeKontenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('platform') !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'Jenis' => ['required', Rule::enum(JenisKontenKampanye::class)],
            'Judul' => ['required', 'string', 'max:190'],
            'Tautan' => ['nullable', 'url', 'max:500'],
            'Catatan' => ['nullable', 'string', 'max:300'],
            'Urutan' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
    }
}
