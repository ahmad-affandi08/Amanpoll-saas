<?php

declare(strict_types=1);

namespace App\Domain\Notifikasi\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanPreferensiNotifikasiRequest extends FormRequest
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
            'PenggunaId' => ['sometimes'],
            'JenisPeristiwa' => ['sometimes'],
            'Kanal' => ['sometimes'],
            'Aktif' => ['sometimes'],
        ];
    }
}
