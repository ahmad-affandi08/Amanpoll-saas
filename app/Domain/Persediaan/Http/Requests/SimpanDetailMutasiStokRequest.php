<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanDetailMutasiStokRequest extends FormRequest
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
            'MutasiStokId' => ['sometimes'],
            'SukuCadangId' => ['sometimes'],
            'KelompokSukuCadangId' => ['nullable'],
            'Jumlah' => ['sometimes'],
            'HargaSatuan' => ['nullable'],
            'LokasiGudangAsalId' => ['nullable'],
            'LokasiGudangTujuanId' => ['nullable'],
        ];
    }
}
