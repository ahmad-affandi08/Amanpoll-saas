<?php

declare(strict_types=1);

namespace App\Domain\Pelaporan\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanLaporanTersimpanRequest extends FormRequest
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
            'Nama' => ['sometimes'],
            'Jenis' => ['sometimes'],
            'Konfigurasi' => ['sometimes'],
            'Pribadi' => ['sometimes'],
            'PemilikId' => ['nullable'],
        ];
    }
}
