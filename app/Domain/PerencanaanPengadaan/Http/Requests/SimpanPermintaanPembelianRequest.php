<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanPermintaanPembelianRequest extends FormRequest
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
            'UnitOrganisasiId' => ['nullable'],
            'RencanaPengadaanId' => ['nullable'],
            'PosAnggaranId' => ['nullable'],
            'TanggalPermintaan' => ['sometimes'],
            'TanggalDibutuhkan' => ['nullable'],
            'Prioritas' => ['sometimes'],
            'Status' => ['sometimes'],
            'Alasan' => ['nullable'],
            'DimintaOleh' => ['sometimes'],
            'TotalEstimasi' => ['sometimes'],
        ];
    }
}
