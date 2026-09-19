<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Http\Requests;

use App\Core\Organisasi\KonteksOrganisasi;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SimpanKompatibilitasSukuCadangRequest extends FormRequest
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

        return [
            'SukuCadangId' => ['required', 'string',
                Rule::exists('SukuCadang', 'Id')->where(fn ($q) => $q->where('OrganisasiId', $organisasiId)->whereNull('DihapusPada'))],
            'KategoriAsetId' => ['nullable', 'string',
                Rule::exists('KategoriAset', 'Id')->where(fn ($q) => $q->where('OrganisasiId', $organisasiId))],
            'ModelAsetId' => ['nullable', 'string',
                Rule::exists('ModelAset', 'Id')->where(fn ($q) => $q->where('OrganisasiId', $organisasiId))],
            'AsetId' => ['nullable', 'string',
                Rule::exists('Aset', 'Id')->where(fn ($q) => $q->where('OrganisasiId', $organisasiId))],
            'Catatan' => ['nullable', 'string'],
        ];
    }
}
