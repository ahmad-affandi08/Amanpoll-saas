<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanTransaksiAnggaranRequest extends FormRequest
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
            'PosAnggaranId' => ['sometimes'],
            'Jenis' => ['sometimes'],
            'ReferensiJenis' => ['nullable'],
            'ReferensiId' => ['nullable'],
            'Jumlah' => ['sometimes'],
            'Tanggal' => ['sometimes'],
            'Keterangan' => ['nullable'],
        ];
    }
}
