<?php

declare(strict_types=1);

namespace App\Domain\Kontrak\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanKontrakRequest extends FormRequest
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
            'PenyediaId' => ['nullable'],
            'Nomor' => ['sometimes'],
            'Nama' => ['sometimes'],
            'Jenis' => ['sometimes'],
            'MulaiPada' => ['sometimes'],
            'BerakhirPada' => ['sometimes'],
            'Nilai' => ['nullable'],
            'MataUang' => ['sometimes'],
            'TingkatLayananId' => ['nullable'],
            'PeringatanHariSebelum' => ['sometimes'],
            'Status' => ['sometimes'],
            'Catatan' => ['nullable'],
        ];
    }
}
