<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Http\Requests;

use App\Core\Organisasi\KonteksOrganisasi;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SimpanDetailRencanaPengadaanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $organisasiId = app(KonteksOrganisasi::class)->wajibId();

        return [
            'UsulanAsetId' => ['nullable', 'string', Rule::exists('UsulanAset', 'Id')->where('OrganisasiId', $organisasiId)],
            'SukuCadangId' => ['nullable', 'string', Rule::exists('SukuCadang', 'Id')->where('OrganisasiId', $organisasiId)],
            'Deskripsi' => ['required_without:UsulanAsetId', 'nullable', 'string', 'max:255'],
            'Jumlah' => ['required_without:UsulanAsetId', 'nullable', 'numeric', 'decimal:0,4', 'min:0.0001'],
            'Satuan' => ['required_without:UsulanAsetId', 'nullable', 'string', 'max:50'],
            'HargaEstimasi' => ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999999999.99'],
            'BulanRencana' => ['nullable', 'integer', 'between:1,12'],
        ];
    }
}
