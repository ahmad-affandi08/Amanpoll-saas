<?php

declare(strict_types=1);

namespace App\Domain\PreventifInspeksi\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanPelaksanaanDaftarPeriksaRequest extends FormRequest
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
            'TemplatDaftarPeriksaId' => ['required', 'string', 'size:26'],
            'PerintahKerjaId' => ['nullable', 'string', 'size:26'],
            'AsetId' => ['nullable', 'string', 'size:26'],
            'DilaksanakanOleh' => ['nullable', 'string', 'size:26'],
            'Catatan' => ['nullable', 'string'],
        ];
    }
}
