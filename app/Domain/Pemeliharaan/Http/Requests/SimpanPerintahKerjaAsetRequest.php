<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanPerintahKerjaAsetRequest extends FormRequest
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
            'PerintahKerjaId' => ['sometimes'],
            'AsetId' => ['sometimes'],
            'Utama' => ['sometimes'],
            'KondisiAwal' => ['nullable'],
            'KondisiAkhir' => ['nullable'],
        ];
    }
}
