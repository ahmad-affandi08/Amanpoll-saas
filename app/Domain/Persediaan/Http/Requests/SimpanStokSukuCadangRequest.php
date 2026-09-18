<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanStokSukuCadangRequest extends FormRequest
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
            'GudangId' => ['sometimes'],
            'LokasiGudangId' => ['nullable'],
            'SukuCadangId' => ['sometimes'],
            'KelompokSukuCadangId' => ['nullable'],
            'JumlahTersedia' => ['sometimes'],
            'JumlahDipesan' => ['sometimes'],
            'JumlahDitahan' => ['sometimes'],
            'Versi' => ['sometimes'],
        ];
    }
}
