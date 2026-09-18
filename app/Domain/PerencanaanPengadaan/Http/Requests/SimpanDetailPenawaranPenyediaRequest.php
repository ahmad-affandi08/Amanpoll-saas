<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanDetailPenawaranPenyediaRequest extends FormRequest
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
            'PenawaranPenyediaId' => ['sometimes'],
            'DetailPermintaanPembelianId' => ['nullable'],
            'Deskripsi' => ['sometimes'],
            'Jumlah' => ['sometimes'],
            'HargaSatuan' => ['sometimes'],
            'Diskon' => ['sometimes'],
            'Pajak' => ['sometimes'],
            'Total' => ['sometimes'],
            'WaktuPengirimanHari' => ['nullable'],
        ];
    }
}
