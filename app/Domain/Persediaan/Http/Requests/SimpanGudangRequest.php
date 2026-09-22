<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Http\Requests;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Persediaan\Domain\Enums\StatusGudang;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\Gudang;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SimpanGudangRequest extends FormRequest
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
        /** @var Gudang|null $gudang */
        $gudang = $this->route('gudang');

        return [
            'Kode' => ['nullable', 'string', 'max:60',
                Rule::unique('Gudang', 'Kode')->where(fn ($q) => $q->where('OrganisasiId', $organisasiId))->ignore($gudang?->Id, 'Id')],
            'Nama' => ['required', 'string', 'max:160'],
            'LokasiId' => ['nullable', 'string',
                Rule::exists('Lokasi', 'Id')->where(fn ($q) => $q->where('OrganisasiId', $organisasiId)->whereNull('DihapusPada'))],
            'PenanggungJawabId' => ['nullable', 'string',
                Rule::exists('Pengguna', 'Id')->where(fn ($q) => $q->where('OrganisasiId', $organisasiId))],
            'Status' => ['required', 'string', Rule::enum(StatusGudang::class)],
        ];
    }
}
