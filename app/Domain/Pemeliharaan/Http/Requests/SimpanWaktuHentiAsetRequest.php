<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanWaktuHentiAsetRequest extends FormRequest
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
            'PerintahKerjaId' => ['nullable'],
            'MulaiPada' => ['sometimes'],
            'SelesaiPada' => ['nullable'],
            'DurasiMenit' => ['nullable'],
            'Jenis' => ['sometimes'],
            'Alasan' => ['nullable'],
        ];
    }
}
