<?php

declare(strict_types=1);

namespace App\Domain\PreventifInspeksi\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanRencanaPemeliharaanAsetRequest extends FormRequest
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
            'RencanaPemeliharaanId' => ['sometimes'],
            'AsetId' => ['sometimes'],
            'TanggalMulai' => ['sometimes'],
            'TanggalBerikutnya' => ['nullable'],
            'NilaiMeterBerikutnya' => ['nullable'],
            'TerakhirDilaksanakanPada' => ['nullable'],
            'Aktif' => ['sometimes'],
        ];
    }
}
