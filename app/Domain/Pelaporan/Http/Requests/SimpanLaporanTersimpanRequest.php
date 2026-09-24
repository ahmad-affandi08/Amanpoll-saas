<?php

declare(strict_types=1);

namespace App\Domain\Pelaporan\Http\Requests;

use App\Domain\Pelaporan\Domain\KatalogKpi;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class SimpanLaporanTersimpanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'Nama' => ['required', 'string', 'max:180'],
            'Jenis' => ['nullable', 'string', 'max:80'],
            'Pribadi' => ['required', 'boolean'],
            'Konfigurasi' => ['required', 'array'],
            'Konfigurasi.KunciKpi' => ['required', 'array', 'min:1', 'max:'.BatasLaporanAsinkron::kpiMaks()],
            'Konfigurasi.KunciKpi.*' => ['required', 'string', Rule::in(KatalogKpi::kunci())],
            'Konfigurasi.Filter' => ['nullable', 'array'],
            'Konfigurasi.Filter.Dari' => ['nullable', 'date'],
            'Konfigurasi.Filter.Sampai' => ['nullable', 'date'],
            'Konfigurasi.Filter.UnitOrganisasiId' => ['nullable', 'array'],
            'Konfigurasi.Filter.UnitOrganisasiId.*' => ['string', 'max:26'],
            'Konfigurasi.Filter.LokasiId' => ['nullable', 'array'],
            'Konfigurasi.Filter.LokasiId.*' => ['string', 'max:26'],
            'Konfigurasi.Filter.UnitPengelolaId' => ['nullable', 'array'],
            'Konfigurasi.Filter.UnitPengelolaId.*' => ['string', 'max:26', new UnitPengelolaLaporanSah],
        ];
    }

    /**
     * Rentang dan jumlah KPI dibatasi untuk laporan yang dihitung di antrean.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                BatasLaporanAsinkron::periksa(
                    $validator,
                    $this->input('Konfigurasi.Filter'),
                    $this->input('Konfigurasi.KunciKpi'),
                    'Konfigurasi.Filter.',
                    'Konfigurasi.KunciKpi',
                );
            },
        ];
    }
}
