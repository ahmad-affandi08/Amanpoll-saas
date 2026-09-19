<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Http\Requests;

use App\Core\Organisasi\KonteksOrganisasi;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SimpanReservasiSukuCadangRequest extends FormRequest
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
            'GudangId' => ['required', 'string',
                Rule::exists('Gudang', 'Id')->where(fn ($q) => $q->where('OrganisasiId', $organisasiId))],
            'SukuCadangId' => ['required', 'string',
                Rule::exists('SukuCadang', 'Id')->where(fn ($q) => $q->where('OrganisasiId', $organisasiId)->whereNull('DihapusPada'))],
            'PerintahKerjaId' => ['nullable', 'string',
                Rule::exists('PerintahKerja', 'Id')->where(fn ($q) => $q->where('OrganisasiId', $organisasiId))],
            'Jumlah' => ['required', 'numeric', 'gt:0'],
            'KadaluarsaPada' => ['nullable', 'date', 'after:now'],
        ];
    }
}
