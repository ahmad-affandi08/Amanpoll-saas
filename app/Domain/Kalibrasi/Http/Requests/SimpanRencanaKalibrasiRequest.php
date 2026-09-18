<?php

declare(strict_types=1);

namespace App\Domain\Kalibrasi\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanRencanaKalibrasiRequest extends FormRequest
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
            'AsetId' => ['sometimes'],
            'JenisKalibrasiId' => ['nullable'],
            'PenyediaId' => ['nullable'],
            'IntervalHari' => ['sometimes'],
            'TanggalMulai' => ['sometimes'],
            'TanggalBerikutnya' => ['sometimes'],
            'PeringatanHariSebelum' => ['sometimes'],
            'Aktif' => ['sometimes'],
        ];
    }
}
