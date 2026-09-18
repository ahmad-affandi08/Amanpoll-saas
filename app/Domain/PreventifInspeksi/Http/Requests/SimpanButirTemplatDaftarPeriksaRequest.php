<?php

declare(strict_types=1);

namespace App\Domain\PreventifInspeksi\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanButirTemplatDaftarPeriksaRequest extends FormRequest
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
            'TemplatDaftarPeriksaId' => ['sometimes'],
            'Urutan' => ['sometimes'],
            'Kode' => ['nullable'],
            'Pertanyaan' => ['sometimes'],
            'TipeJawaban' => ['sometimes'],
            'Satuan' => ['nullable'],
            'Wajib' => ['sometimes'],
            'NilaiMinimum' => ['nullable'],
            'NilaiMaksimum' => ['nullable'],
            'Pilihan' => ['nullable'],
            'BuktiFotoWajib' => ['sometimes'],
            'MemicuTemuanJika' => ['nullable'],
        ];
    }
}
