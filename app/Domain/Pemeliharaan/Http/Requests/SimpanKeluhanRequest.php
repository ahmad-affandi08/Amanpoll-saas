<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanKeluhanRequest extends FormRequest
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
            'KategoriKeluhanId' => ['nullable'],
            'AsetId' => ['nullable'],
            'LokasiId' => ['nullable'],
            'Judul' => ['sometimes'],
            'Deskripsi' => ['sometimes'],
            'Prioritas' => ['sometimes'],
            'Status' => ['sometimes'],
            'Sumber' => ['sometimes'],
            'PelaporId' => ['nullable'],
            'NamaPelaporEksternal' => ['nullable'],
            'KontakPelaporEksternal' => ['nullable'],
            'DilaporkanPada' => ['sometimes'],
            'DiresponsPada' => ['nullable'],
            'DitutupPada' => ['nullable'],
            'Rating' => ['nullable'],
            'Ulasan' => ['nullable'],
            'Versi' => ['sometimes'],
        ];
    }
}
