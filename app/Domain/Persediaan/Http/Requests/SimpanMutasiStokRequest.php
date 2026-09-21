<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Http\Requests;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Persediaan\Domain\Enums\JenisMutasiStok;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SimpanMutasiStokRequest extends FormRequest
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
            'Jenis' => ['required', 'string', Rule::enum(JenisMutasiStok::class)],
            'GudangAsalId' => ['nullable', 'string',
                Rule::exists('Gudang', 'Id')->where(fn ($q) => $q->where('OrganisasiId', $organisasiId))],
            'GudangTujuanId' => ['nullable', 'string',
                Rule::exists('Gudang', 'Id')->where(fn ($q) => $q->where('OrganisasiId', $organisasiId))],
            'ReferensiJenis' => ['nullable', 'string', 'max:80'],
            'ReferensiId' => ['nullable', 'string'],
            'Tanggal' => ['nullable', 'date'],
            'Catatan' => ['nullable', 'string'],
        ];
    }
}
