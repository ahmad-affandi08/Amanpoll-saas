<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanAnggaranRequest extends FormRequest
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
            'UnitOrganisasiId' => ['nullable'],
            'Kode' => ['sometimes'],
            'Nama' => ['sometimes'],
            'Tahun' => ['sometimes'],
            'MataUang' => ['sometimes'],
            'Jumlah' => ['sometimes'],
            'Status' => ['sometimes'],
        ];
    }
}
