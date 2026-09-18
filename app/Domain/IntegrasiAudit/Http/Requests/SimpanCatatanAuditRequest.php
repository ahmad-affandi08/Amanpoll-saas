<?php

declare(strict_types=1);

namespace App\Domain\IntegrasiAudit\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanCatatanAuditRequest extends FormRequest
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
            'Aksi' => ['sometimes'],
            'JenisEntitas' => ['sometimes'],
            'EntitasId' => ['nullable'],
            'DataSebelum' => ['nullable'],
            'DataSesudah' => ['nullable'],
            'AlamatIp' => ['nullable'],
            'AgenPengguna' => ['nullable'],
            'KorelasiId' => ['nullable'],
        ];
    }
}
