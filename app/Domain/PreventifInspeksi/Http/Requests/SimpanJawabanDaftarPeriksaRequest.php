<?php

declare(strict_types=1);

namespace App\Domain\PreventifInspeksi\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanJawabanDaftarPeriksaRequest extends FormRequest
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
            'PelaksanaanDaftarPeriksaId' => ['sometimes'],
            'ButirTemplatDaftarPeriksaId' => ['sometimes'],
            'NilaiTeks' => ['nullable'],
            'NilaiAngka' => ['nullable'],
            'NilaiBoolean' => ['nullable'],
            'NilaiTanggal' => ['nullable'],
            'NilaiJson' => ['nullable'],
            'Sesuai' => ['nullable'],
            'Catatan' => ['nullable'],
            'DijawabPada' => ['nullable'],
        ];
    }
}
