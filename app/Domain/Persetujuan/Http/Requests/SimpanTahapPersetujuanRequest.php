<?php

declare(strict_types=1);

namespace App\Domain\Persetujuan\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanTahapPersetujuanRequest extends FormRequest
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
            'AlurPersetujuanId' => ['sometimes'],
            'Urutan' => ['sometimes'],
            'Nama' => ['sometimes'],
            'JenisPenyetuju' => ['sometimes'],
            'PeranId' => ['nullable'],
            'PenggunaId' => ['nullable'],
            'JumlahMinimumPenyetuju' => ['sometimes'],
            'BolehMenyetujuiSendiri' => ['sometimes'],
            'BatasWaktuMenit' => ['nullable'],
            'Kondisi' => ['nullable'],
        ];
    }
}
