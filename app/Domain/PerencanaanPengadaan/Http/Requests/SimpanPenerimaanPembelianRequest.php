<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Http\Requests;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Persediaan\Http\Requests\GudangTerlihat;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SimpanPenerimaanPembelianRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $organisasiId = app(KonteksOrganisasi::class)->wajibId();

        return [
            'Nomor' => ['nullable', 'string', 'max:100'],
            // Barang hanya boleh diterima ke gudang yang terlihat penerimanya (PRD 8.21).
            'GudangId' => ['nullable', 'string', new GudangTerlihat],
            'TanggalTerima' => ['required', 'date'],
            'NomorSuratJalan' => ['nullable', 'string', 'max:160'],
            'Catatan' => ['nullable', 'string', 'max:3000'],
            'Detail' => ['required', 'array', 'min:1'],
            'Detail.*.DetailPesananPembelianId' => ['required', 'string', 'distinct', Rule::exists('DetailPesananPembelian', 'Id')->where('OrganisasiId', $organisasiId)],
            'Detail.*.JumlahDiterima' => ['required', 'numeric', 'decimal:0,4', 'gt:0'],
            'Detail.*.JumlahDitolak' => ['nullable', 'numeric', 'decimal:0,4', 'min:0'],
            'Detail.*.Kondisi' => ['required', Rule::in(['Baik', 'RusakRingan', 'Rusak'])],
            'Detail.*.NomorSeri' => ['nullable', 'array'],
            'Detail.*.NomorSeri.*' => ['nullable', 'string', 'max:160', 'distinct'],
            'Detail.*.Catatan' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
