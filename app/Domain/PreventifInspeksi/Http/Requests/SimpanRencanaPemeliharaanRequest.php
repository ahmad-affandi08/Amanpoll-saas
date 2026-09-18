<?php

declare(strict_types=1);

namespace App\Domain\PreventifInspeksi\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanRencanaPemeliharaanRequest extends FormRequest
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
            'Kode' => ['sometimes'],
            'Nama' => ['sometimes'],
            'Jenis' => ['sometimes'],
            'TemplatDaftarPeriksaId' => ['nullable'],
            'Prioritas' => ['sometimes'],
            'StrategiJadwal' => ['sometimes'],
            'IntervalNilai' => ['nullable'],
            'IntervalSatuan' => ['nullable'],
            'BerdasarkanMeter' => ['sometimes'],
            'AmbangMeter' => ['nullable'],
            'ToleransiHari' => ['sometimes'],
            'BuatPerintahKerjaHariSebelum' => ['sometimes'],
            'Aktif' => ['sometimes'],
        ];
    }
}
