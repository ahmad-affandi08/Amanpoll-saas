<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Http\Requests;

use App\Core\Organisasi\KonteksOrganisasi;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SimpanPenawaranPenyediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $organisasiId = app(KonteksOrganisasi::class)->wajibId();

        return [
            'PenyediaId' => ['required', 'string', Rule::exists('Penyedia', 'Id')->where('OrganisasiId', $organisasiId)],
            'NomorPenawaran' => ['nullable', 'string', 'max:100'],
            'TanggalPenawaran' => ['required', 'date'],
            'BerlakuSampai' => ['nullable', 'date', 'after_or_equal:TanggalPenawaran'],
            'MataUang' => ['required', 'string', 'size:3'],
            'Catatan' => ['nullable', 'string', 'max:3000'],
            'Detail' => ['required', 'array', 'min:1'],
            'Detail.*.DetailPermintaanPembelianId' => ['required', 'string', 'distinct', Rule::exists('DetailPermintaanPembelian', 'Id')->where('OrganisasiId', $organisasiId)],
            'Detail.*.Jumlah' => ['required', 'numeric', 'decimal:0,4', 'gt:0'],
            'Detail.*.HargaSatuan' => ['required', 'numeric', 'decimal:0,2', 'min:0'],
            'Detail.*.Diskon' => ['nullable', 'numeric', 'decimal:0,2', 'min:0'],
            'Detail.*.Pajak' => ['nullable', 'numeric', 'decimal:0,2', 'min:0'],
            'Detail.*.WaktuPengirimanHari' => ['nullable', 'integer', 'min:0', 'max:3650'],
        ];
    }
}
