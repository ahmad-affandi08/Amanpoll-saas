<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanRencanaPengadaanRequest extends FormRequest
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
            'Nama' => ['sometimes'],
            'Tahun' => ['sometimes'],
            'PosAnggaranId' => ['nullable'],
            'Status' => ['sometimes'],
            'TotalEstimasi' => ['sometimes'],
            'DibuatOleh' => ['nullable'],
        ];
    }
}
