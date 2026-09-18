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

    public function rules(): array
    {
        // Perketat rule sesuai invariant use-case sebelum endpoint diaktifkan.
        return [
            'OrganisasiId' => ['sometimes'],
            'TemplatDaftarPeriksaId' => ['sometimes'],
            'PerintahKerjaId' => ['nullable'],
            'AsetId' => ['nullable'],
            'DilaksanakanOleh' => ['nullable'],
            'MulaiPada' => ['nullable'],
            'SelesaiPada' => ['nullable'],
            'Status' => ['sometimes'],
            'Skor' => ['nullable'],
            'Catatan' => ['nullable'],
        ];
    }
}
