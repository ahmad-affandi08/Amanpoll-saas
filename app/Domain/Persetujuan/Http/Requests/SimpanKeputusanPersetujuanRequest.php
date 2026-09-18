<?php

declare(strict_types=1);

namespace App\Domain\Persetujuan\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanKeputusanPersetujuanRequest extends FormRequest
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
            'PermintaanPersetujuanId' => ['sometimes'],
            'TahapPersetujuanId' => ['sometimes'],
            'PenyetujuId' => ['sometimes'],
            'Keputusan' => ['sometimes'],
            'Catatan' => ['nullable'],
            'DiputuskanPada' => ['sometimes'],
        ];
    }
}
