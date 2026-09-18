<?php

declare(strict_types=1);

namespace App\Domain\Penyedia\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanPenilaianPenyediaRequest extends FormRequest
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
            'PenyediaId' => ['sometimes'],
            'PeriodeMulai' => ['sometimes'],
            'PeriodeSelesai' => ['sometimes'],
            'SkorKualitas' => ['nullable'],
            'SkorKetepatanWaktu' => ['nullable'],
            'SkorHarga' => ['nullable'],
            'SkorLayanan' => ['nullable'],
            'SkorTotal' => ['nullable'],
            'Catatan' => ['nullable'],
            'DinilaiOleh' => ['nullable'],
        ];
    }
}
