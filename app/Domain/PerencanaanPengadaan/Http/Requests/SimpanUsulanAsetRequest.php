<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Http\Requests;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\PerencanaanPengadaan\Domain\Enums\PrioritasUsulanAset;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SimpanUsulanAsetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $wajib = $this->isMethod('post') ? 'required' : 'sometimes';
        $organisasiId = app(KonteksOrganisasi::class)->wajibId();

        return [
            'Nomor' => ['nullable', 'string', 'max:100'],
            'UnitOrganisasiId' => [$wajib, 'string', Rule::exists('UnitOrganisasi', 'Id')->where('OrganisasiId', $organisasiId)],
            'KategoriAsetId' => ['nullable', 'string', Rule::exists('KategoriAset', 'Id')->where('OrganisasiId', $organisasiId)],
            'ModelAsetId' => ['nullable', 'string', Rule::exists('ModelAset', 'Id')->where('OrganisasiId', $organisasiId)],
            'NamaKebutuhan' => [$wajib, 'string', 'max:220'],
            'Jumlah' => [$wajib, 'numeric', 'decimal:0,4', 'min:0.0001', 'max:9999999999.9999'],
            'EstimasiHargaSatuan' => ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999999999.99'],
            'Alasan' => [$wajib, 'string', 'max:5000'],
            'JenisKebutuhan' => ['nullable', 'string', 'max:60'],
            'TahunKebutuhan' => ['nullable', 'integer', 'between:2000,2100'],
            'Prioritas' => ['sometimes', 'string', Rule::enum(PrioritasUsulanAset::class)],
        ];
    }
}
