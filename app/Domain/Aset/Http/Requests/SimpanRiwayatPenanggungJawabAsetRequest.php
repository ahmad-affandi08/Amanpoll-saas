<?php

declare(strict_types=1);

namespace App\Domain\Aset\Http\Requests;

use App\Core\Organisasi\KonteksOrganisasi;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SimpanRiwayatPenanggungJawabAsetRequest extends FormRequest
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
            'PenggunaId' => ['required_without:UnitOrganisasiId', 'nullable', 'string',
                Rule::exists('Pengguna', 'Id')->where(fn ($q) => $q->where('OrganisasiId', $organisasiId))],
            'UnitOrganisasiId' => ['required_without:PenggunaId', 'nullable', 'string',
                Rule::exists('UnitOrganisasi', 'Id')->where(fn ($q) => $q->where('OrganisasiId', $organisasiId)->whereNull('DihapusPada'))],
            'Catatan' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
