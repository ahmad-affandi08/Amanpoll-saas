<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanPermintaanPenawaranRequest extends FormRequest
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
            'PermintaanPembelianId' => ['nullable'],
            'TanggalDibuka' => ['sometimes'],
            'BatasPenawaran' => ['nullable'],
            'Status' => ['sometimes'],
            'Catatan' => ['nullable'],
            'DibuatOleh' => ['nullable'],
        ];
    }
}
