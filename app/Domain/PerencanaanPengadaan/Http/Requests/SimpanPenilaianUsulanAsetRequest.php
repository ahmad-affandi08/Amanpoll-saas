<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanPenilaianUsulanAsetRequest extends FormRequest
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
            'UsulanAsetId' => ['sometimes'],
            'Kriteria' => ['sometimes'],
            'Bobot' => ['sometimes'],
            'Nilai' => ['sometimes'],
            'Skor' => ['sometimes'],
            'DinilaiOleh' => ['nullable'],
            'DinilaiPada' => ['sometimes'],
        ];
    }
}
