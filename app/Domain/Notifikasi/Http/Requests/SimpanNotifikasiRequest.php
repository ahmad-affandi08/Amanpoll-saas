<?php

declare(strict_types=1);

namespace App\Domain\Notifikasi\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanNotifikasiRequest extends FormRequest
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
            'PenggunaId' => ['nullable'],
            'Kanal' => ['sometimes'],
            'JenisPeristiwa' => ['sometimes'],
            'Judul' => ['nullable'],
            'Isi' => ['sometimes'],
            'JenisEntitas' => ['nullable'],
            'EntitasId' => ['nullable'],
            'Status' => ['sometimes'],
            'JadwalKirimPada' => ['nullable'],
            'DikirimPada' => ['nullable'],
            'DibacaPada' => ['nullable'],
            'Percobaan' => ['sometimes'],
            'KesalahanTerakhir' => ['nullable'],
        ];
    }
}
