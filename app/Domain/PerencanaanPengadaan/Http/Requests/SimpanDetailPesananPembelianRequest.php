<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanDetailPesananPembelianRequest extends FormRequest
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
            'PesananPembelianId' => ['sometimes'],
            'JenisItem' => ['sometimes'],
            'SukuCadangId' => ['nullable'],
            'Deskripsi' => ['sometimes'],
            'Jumlah' => ['sometimes'],
            'Satuan' => ['sometimes'],
            'HargaSatuan' => ['sometimes'],
            'Diskon' => ['sometimes'],
            'Pajak' => ['sometimes'],
            'Total' => ['sometimes'],
        ];
    }
}
