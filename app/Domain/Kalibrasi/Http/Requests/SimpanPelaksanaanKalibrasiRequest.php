<?php

declare(strict_types=1);

namespace App\Domain\Kalibrasi\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanPelaksanaanKalibrasiRequest extends FormRequest
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
            'Nomor' => ['sometimes'],
            'RencanaKalibrasiId' => ['nullable'],
            'AsetId' => ['sometimes'],
            'JenisKalibrasiId' => ['nullable'],
            'PenyediaId' => ['nullable'],
            'PerintahKerjaId' => ['nullable'],
            'TanggalKalibrasi' => ['sometimes'],
            'TanggalBerlakuSampai' => ['nullable'],
            'Hasil' => ['sometimes'],
            'NomorSertifikat' => ['nullable'],
            'Laboratorium' => ['nullable'],
            'KondisiLingkungan' => ['nullable'],
            'Catatan' => ['nullable'],
            'DilaksanakanOleh' => ['nullable'],
            'DiverifikasiOleh' => ['nullable'],
            'DiverifikasiPada' => ['nullable'],
        ];
    }
}
