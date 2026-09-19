<?php

declare(strict_types=1);

namespace App\Domain\SiklusAset\Http\Requests;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SimpanDetailSerahTerimaAsetRequest extends FormRequest
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
            'AsetId' => ['required', 'string',
                Rule::exists('Aset', 'Id')->where(fn ($q) => $q->where('OrganisasiId', $organisasiId)->whereNull('DihapusPada'))],
            'KondisiSaatDiserahkan' => ['nullable', 'string', Rule::in([Aset::KONDISI_BAIK, Aset::KONDISI_PERLU_PERHATIAN, Aset::KONDISI_RUSAK])],
            'Catatan' => ['nullable', 'string'],
        ];
    }
}
