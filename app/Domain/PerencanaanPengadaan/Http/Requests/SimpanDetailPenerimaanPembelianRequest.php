<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanDetailPenerimaanPembelianRequest extends FormRequest
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
            'PenerimaanPembelianId' => ['sometimes'],
            'DetailPesananPembelianId' => ['nullable'],
            'SukuCadangId' => ['nullable'],
            'JumlahDipesan' => ['sometimes'],
            'JumlahDiterima' => ['sometimes'],
            'JumlahDitolak' => ['sometimes'],
            'Kondisi' => ['nullable'],
            'NomorSeriJson' => ['nullable'],
            'Catatan' => ['nullable'],
        ];
    }
}
