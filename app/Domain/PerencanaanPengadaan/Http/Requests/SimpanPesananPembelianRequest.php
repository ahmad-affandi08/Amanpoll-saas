<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanPesananPembelianRequest extends FormRequest
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
            'PenyediaId' => ['sometimes'],
            'PermintaanPembelianId' => ['nullable'],
            'PenawaranPenyediaId' => ['nullable'],
            'PosAnggaranId' => ['nullable'],
            'TanggalPesanan' => ['sometimes'],
            'TanggalKirimRencana' => ['nullable'],
            'MataUang' => ['sometimes'],
            'Subtotal' => ['sometimes'],
            'Pajak' => ['sometimes'],
            'Diskon' => ['sometimes'],
            'Total' => ['sometimes'],
            'Status' => ['sometimes'],
            'Catatan' => ['nullable'],
            'DibuatOleh' => ['nullable'],
        ];
    }
}
