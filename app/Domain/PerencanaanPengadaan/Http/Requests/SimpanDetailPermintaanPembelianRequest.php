<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanDetailPermintaanPembelianRequest extends FormRequest
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
            'PermintaanPembelianId' => ['sometimes'],
            'JenisItem' => ['sometimes'],
            'AsetReferensiId' => ['nullable'],
            'SukuCadangId' => ['nullable'],
            'Deskripsi' => ['sometimes'],
            'Jumlah' => ['sometimes'],
            'Satuan' => ['sometimes'],
            'HargaEstimasi' => ['nullable'],
            'Spesifikasi' => ['nullable'],
        ];
    }
}
