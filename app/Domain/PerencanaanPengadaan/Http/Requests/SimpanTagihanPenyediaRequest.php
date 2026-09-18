<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanTagihanPenyediaRequest extends FormRequest
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
            'PenyediaId' => ['sometimes'],
            'PesananPembelianId' => ['nullable'],
            'NomorTagihan' => ['sometimes'],
            'TanggalTagihan' => ['sometimes'],
            'JatuhTempo' => ['nullable'],
            'Subtotal' => ['sometimes'],
            'Pajak' => ['sometimes'],
            'Total' => ['sometimes'],
            'Sisa' => ['sometimes'],
            'Status' => ['sometimes'],
        ];
    }
}
