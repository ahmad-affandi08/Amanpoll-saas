<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Http\Requests;

use App\Core\Organisasi\KonteksOrganisasi;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SimpanRencanaPengadaanRequest extends FormRequest
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
            'Nama' => [$wajib, 'string', 'max:200'],
            'Tahun' => [$wajib, 'integer', 'between:2000,2100'],
            'PosAnggaranId' => ['nullable', 'string', Rule::exists('PosAnggaran', 'Id')->where('OrganisasiId', $organisasiId)],
            'UsulanAsetIds' => ['sometimes', 'array', 'distinct'],
            'UsulanAsetIds.*' => ['string', Rule::exists('UsulanAset', 'Id')->where('OrganisasiId', $organisasiId)],
        ];
    }
}
