<?php

declare(strict_types=1);

namespace App\Domain\Langganan\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanPembayaranLanggananRequest extends FormRequest
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
            'TagihanLanggananId' => ['sometimes'],
            'PenyediaPembayaran' => ['nullable'],
            'ReferensiEksternal' => ['nullable'],
            'Metode' => ['nullable'],
            'Jumlah' => ['sometimes'],
            'Status' => ['sometimes'],
            'DibayarPada' => ['nullable'],
            'MuatanData' => ['nullable'],
        ];
    }
}
