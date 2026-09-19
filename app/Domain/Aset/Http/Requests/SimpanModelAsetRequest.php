<?php

declare(strict_types=1);

namespace App\Domain\Aset\Http\Requests;

use App\Core\Organisasi\KonteksOrganisasi;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SimpanModelAsetRequest extends FormRequest
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
            'KategoriAsetId' => ['required', 'string',
                Rule::exists('KategoriAset', 'Id')->where(fn ($q) => $q->where('OrganisasiId', $organisasiId)->whereNull('DihapusPada'))],
            'MerekId' => ['nullable', 'string',
                Rule::exists('Merek', 'Id')->where(fn ($q) => $q->where('OrganisasiId', $organisasiId))],
            'KodeModel' => ['nullable', 'string', 'max:100'],
            'Nama' => ['required', 'string', 'max:180'],
            'Produsen' => ['nullable', 'string', 'max:180'],
            'Spesifikasi' => ['nullable', 'array'],
            'IntervalPemeliharaanHari' => ['nullable', 'integer', 'min:1'],
            'IntervalKalibrasiHari' => ['nullable', 'integer', 'min:1'],
            'UmurManfaatBulan' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
