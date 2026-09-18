<?php

declare(strict_types=1);

namespace App\Domain\PreventifInspeksi\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanJadwalPemeliharaanRequest extends FormRequest
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
            'RencanaPemeliharaanAsetId' => ['sometimes'],
            'PerintahKerjaId' => ['nullable'],
            'TanggalJadwal' => ['sometimes'],
            'Status' => ['sometimes'],
            'DihasilkanOtomatis' => ['sometimes'],
        ];
    }
}
