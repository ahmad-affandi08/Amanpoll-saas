<?php

declare(strict_types=1);

namespace App\Domain\Platform\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanPenggunaRequest extends FormRequest
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
            'UnitOrganisasiId' => ['nullable'],
            'Nama' => ['sometimes'],
            'Email' => ['sometimes'],
            'Telepon' => ['nullable'],
            'KataSandi' => ['nullable'],
            'EmailTerverifikasiPada' => ['nullable'],
            'AvatarUrl' => ['nullable'],
            'NomorPegawai' => ['nullable'],
            'Jabatan' => ['nullable'],
            'JenisPengguna' => ['sometimes'],
            'Status' => ['sometimes'],
            'TerakhirMasukPada' => ['nullable'],
        ];
    }
}
