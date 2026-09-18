<?php

declare(strict_types=1);

namespace App\Domain\Aset\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanRiwayatLokasiAsetRequest extends FormRequest
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
            'LokasiAsalId' => ['nullable'],
            'LokasiTujuanId' => ['nullable'],
            'JenisPerpindahan' => ['sometimes'],
            'ReferensiJenis' => ['nullable'],
            'ReferensiId' => ['nullable'],
            'Alasan' => ['nullable'],
            'DipindahkanOleh' => ['nullable'],
            'DipindahkanPada' => ['sometimes'],
        ];
    }
}
