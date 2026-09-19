<?php

declare(strict_types=1);

namespace App\Domain\Aset\Http\Requests;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Aset\Infrastructure\Persistence\Models\KategoriAset;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SimpanKategoriAsetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $organisasiId = app(KonteksOrganisasi::class)->id();
        /** @var KategoriAset|null $kategoriAset */
        $kategoriAset = $this->route('kategoriAset');
        $kategoriAsetId = $kategoriAset?->Id;

        return [
            'Kode' => ['required', 'string', 'max:60',
                Rule::unique('KategoriAset', 'Kode')->where(fn ($q) => $q->where('OrganisasiId', $organisasiId))->whereNull('DihapusPada')->ignore($kategoriAsetId, 'Id')],
            'Nama' => ['required', 'string', 'max:160'],
            'IndukId' => ['nullable', 'string',
                Rule::exists('KategoriAset', 'Id')->where(fn ($q) => $q->where('OrganisasiId', $organisasiId)->whereNull('DihapusPada'))],
            'UmurManfaatBulan' => ['nullable', 'integer', 'min:1'],
            'MetodePenyusutanBawaan' => ['nullable', 'string', 'max:40'],
            'PersentaseNilaiResidu' => ['nullable', 'numeric', 'between:0,100'],
            'MemerlukanKalibrasi' => ['boolean'],
            'MemerlukanPemeliharaan' => ['boolean'],
        ];
    }
}
