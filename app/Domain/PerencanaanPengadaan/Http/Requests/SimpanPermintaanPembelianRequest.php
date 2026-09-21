<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Http\Requests;

use App\Core\Organisasi\KonteksOrganisasi;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SimpanPermintaanPembelianRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $organisasiId = app(KonteksOrganisasi::class)->wajibId();

        return [
            'UnitOrganisasiId' => ['nullable', 'string', Rule::exists('UnitOrganisasi', 'Id')->where('OrganisasiId', $organisasiId)],
            'RencanaPengadaanId' => ['nullable', 'string', Rule::exists('RencanaPengadaan', 'Id')->where('OrganisasiId', $organisasiId)],
            'PosAnggaranId' => ['required', 'string', Rule::exists('PosAnggaran', 'Id')->where('OrganisasiId', $organisasiId)],
            'TanggalPermintaan' => ['nullable', 'date'],
            'TanggalDibutuhkan' => ['nullable', 'date', 'after_or_equal:TanggalPermintaan'],
            'Prioritas' => ['required', Rule::in(['Rendah', 'Normal', 'Tinggi', 'Mendesak'])],
            'Alasan' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
