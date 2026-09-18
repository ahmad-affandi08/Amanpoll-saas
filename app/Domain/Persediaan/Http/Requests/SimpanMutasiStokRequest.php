<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanMutasiStokRequest extends FormRequest
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
            'Jenis' => ['sometimes'],
            'GudangAsalId' => ['nullable'],
            'GudangTujuanId' => ['nullable'],
            'ReferensiJenis' => ['nullable'],
            'ReferensiId' => ['nullable'],
            'Tanggal' => ['sometimes'],
            'Status' => ['sometimes'],
            'Catatan' => ['nullable'],
            'DibuatOleh' => ['nullable'],
        ];
    }
}
