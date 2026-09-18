<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanKelompokSukuCadangRequest extends FormRequest
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
            'SukuCadangId' => ['sometimes'],
            'NomorBatch' => ['sometimes'],
            'TanggalProduksi' => ['nullable'],
            'TanggalKadaluarsa' => ['nullable'],
            'HargaPerolehan' => ['nullable'],
        ];
    }
}
