<?php

declare(strict_types=1);

namespace App\Domain\Pelaporan\Http\Requests;

use App\Domain\Pelaporan\Domain\KatalogKpi;
use App\Shared\Infrastructure\Ekspor\FormatEkspor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class MintaEksporLaporanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'Judul' => ['required', 'string', 'max:180'],
            'Format' => ['required', Rule::enum(FormatEkspor::class)],
            'KunciKpi' => ['required', 'array', 'min:1', 'max:30'],
            'KunciKpi.*' => ['required', 'string', Rule::in(KatalogKpi::kunci())],
            'Filter' => ['nullable', 'array'],
            'Filter.Dari' => ['nullable', 'date'],
            'Filter.Sampai' => ['nullable', 'date'],
            'Filter.UnitOrganisasiId' => ['nullable', 'array'],
            'Filter.UnitOrganisasiId.*' => ['string', 'max:26'],
            'Filter.LokasiId' => ['nullable', 'array'],
            'Filter.LokasiId.*' => ['string', 'max:26'],
            'Filter.UnitPengelolaId' => ['nullable', 'array'],
            'Filter.UnitPengelolaId.*' => ['string', 'max:26', new UnitPengelolaLaporanSah],
        ];
    }
}
