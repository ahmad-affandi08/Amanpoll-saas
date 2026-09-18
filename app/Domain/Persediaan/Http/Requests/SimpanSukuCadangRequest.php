<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanSukuCadangRequest extends FormRequest
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
            'KategoriSukuCadangId' => ['nullable'],
            'Kode' => ['sometimes'],
            'Nama' => ['sometimes'],
            'NomorBagian' => ['nullable'],
            'KodeBatang' => ['nullable'],
            'SatuanDasar' => ['sometimes'],
            'StokMinimum' => ['sometimes'],
            'StokMaksimum' => ['nullable'],
            'TitikPesanUlang' => ['nullable'],
            'HargaRataRata' => ['sometimes'],
            'MemakaiBatch' => ['sometimes'],
            'MemakaiKadaluarsa' => ['sometimes'],
            'Status' => ['sometimes'],
        ];
    }
}
