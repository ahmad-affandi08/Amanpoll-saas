<?php

declare(strict_types=1);

namespace App\Domain\Aset\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanRiwayatPenanggungJawabAsetRequest extends FormRequest
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
            'PenggunaId' => ['nullable'],
            'UnitOrganisasiId' => ['nullable'],
            'MulaiPada' => ['sometimes'],
            'SelesaiPada' => ['nullable'],
            'Catatan' => ['nullable'],
        ];
    }
}
