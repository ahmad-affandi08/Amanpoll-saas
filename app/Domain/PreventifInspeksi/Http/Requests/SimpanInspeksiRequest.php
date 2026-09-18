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

    public function rules(): array
    {
        // Perketat rule sesuai invariant use-case sebelum endpoint diaktifkan.
        return [
            'OrganisasiId' => ['sometimes'],
            'Nomor' => ['sometimes'],
            'TemplatInspeksiId' => ['sometimes'],
            'AsetId' => ['sometimes'],
            'PelaksanaanDaftarPeriksaId' => ['nullable'],
            'DijadwalkanPada' => ['nullable'],
            'DilaksanakanPada' => ['nullable'],
            'Status' => ['sometimes'],
            'Hasil' => ['nullable'],
            'Temuan' => ['nullable'],
            'TindakLanjut' => ['nullable'],
            'PerintahKerjaId' => ['nullable'],
            'DilaksanakanOleh' => ['nullable'],
        ];
    }
}
