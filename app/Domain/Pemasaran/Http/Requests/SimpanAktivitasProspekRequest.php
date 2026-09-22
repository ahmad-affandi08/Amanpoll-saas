<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Http\Requests;

use App\Domain\Pemasaran\Domain\Enums\JenisAktivitasProspek;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SimpanAktivitasProspekRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('platform') !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'Jenis' => ['required', Rule::enum(JenisAktivitasProspek::class)],
            'Judul' => ['required', 'string', 'max:190'],
            'Isi' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
