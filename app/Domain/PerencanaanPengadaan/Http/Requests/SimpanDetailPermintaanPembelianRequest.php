<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Http\Requests;

use App\Core\Organisasi\KonteksOrganisasi;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SimpanDetailPermintaanPembelianRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $organisasiId = app(KonteksOrganisasi::class)->wajibId();

        return [
            'JenisItem' => ['required', Rule::in(['Aset', 'SukuCadang', 'Jasa', 'Lainnya'])],
            'AsetReferensiId' => ['nullable', 'string', Rule::exists('Aset', 'Id')->where('OrganisasiId', $organisasiId)],
            'SukuCadangId' => ['nullable', 'string', Rule::exists('SukuCadang', 'Id')->where('OrganisasiId', $organisasiId)],
            'Deskripsi' => ['required', 'string', 'max:500'],
            'Jumlah' => ['required', 'numeric', 'decimal:0,4', 'gt:0'],
            'Satuan' => ['required', 'string', 'max:30'],
            'HargaEstimasi' => ['required', 'numeric', 'decimal:0,2', 'min:0'],
            'Spesifikasi' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
