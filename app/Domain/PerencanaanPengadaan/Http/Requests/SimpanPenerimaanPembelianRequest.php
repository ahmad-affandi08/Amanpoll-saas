<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanPenerimaanPembelianRequest extends FormRequest
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
            'PesananPembelianId' => ['sometimes'],
            'GudangId' => ['nullable'],
            'TanggalTerima' => ['sometimes'],
            'NomorSuratJalan' => ['nullable'],
            'DiterimaOleh' => ['nullable'],
            'Status' => ['sometimes'],
            'Catatan' => ['nullable'],
        ];
    }
}
