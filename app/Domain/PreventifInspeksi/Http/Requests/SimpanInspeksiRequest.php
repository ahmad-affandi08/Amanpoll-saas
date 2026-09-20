<?php

declare(strict_types=1);

namespace App\Domain\PreventifInspeksi\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanInspeksiRequest extends FormRequest
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
            'Nomor' => ['nullable', 'string', 'max:100'],
            'TemplatInspeksiId' => ['required', 'string', 'size:26'],
            'AsetId' => ['required', 'string', 'size:26'],
            'DijadwalkanPada' => ['required', 'date'],
            'DilaksanakanOleh' => ['nullable', 'string', 'size:26'],
        ];
    }
}
