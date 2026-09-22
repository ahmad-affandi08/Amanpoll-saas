<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Http\Requests;

use App\Core\Organisasi\KonteksOrganisasi;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SimpanKodeKegagalanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $organisasiId = app(KonteksOrganisasi::class)->wajibId();

        return [
            'KategoriAsetId' => ['nullable', 'string', Rule::exists('KategoriAset', 'Id')->where(fn ($query) => $query->where('OrganisasiId', $organisasiId)->whereNull('DihapusPada'))],
            'Kode' => ['nullable', 'string', 'max:60', Rule::unique('KodeKegagalan', 'Kode')->where(fn ($query) => $query->where('OrganisasiId', $organisasiId))->ignore($this->route('kodeKegagalan'))],
            'Nama' => ['required', 'string', 'max:180'],
            'Jenis' => ['required', Rule::in(['Masalah', 'Penyebab', 'Tindakan'])],
            'Keterangan' => ['nullable', 'string', 'max:2000'],
            'Aktif' => ['required', 'boolean'],
        ];
    }
}
