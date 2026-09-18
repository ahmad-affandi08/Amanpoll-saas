<?php

declare(strict_types=1);

namespace App\Domain\IntegrasiAudit\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanCatatanAksesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        // Perketat rule sesuai invariant use-case sebelum endpoint diaktifkan.
        return [
            'OrganisasiId' => ['nullable'],
            'PenggunaId' => ['nullable'],
            'Jenis' => ['sometimes'],
            'AlamatIp' => ['nullable'],
            'AgenPengguna' => ['nullable'],
            'Berhasil' => ['sometimes'],
            'AlasanGagal' => ['nullable'],
        ];
    }
}
