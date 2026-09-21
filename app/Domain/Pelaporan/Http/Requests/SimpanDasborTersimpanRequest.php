<?php

declare(strict_types=1);

namespace App\Domain\Pelaporan\Http\Requests;

use App\Domain\Pelaporan\Application\Actions\KelolaDasborTersimpan;
use App\Domain\Pelaporan\Application\Services\LayananDasbor;
use App\Domain\Pelaporan\Domain\Enums\BentukKomponen;
use App\Domain\Pelaporan\Domain\KatalogKpi;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SimpanDasborTersimpanRequest extends FormRequest
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
            'Bawaan' => ['nullable', 'boolean'],
            'Komponen' => ['required', 'array', 'min:1', 'max:'.KelolaDasborTersimpan::BATAS_KOMPONEN],
            'Komponen.*.KunciKpi' => ['required', 'string', Rule::in(KatalogKpi::kunci())],
            'Komponen.*.Bentuk' => ['required', Rule::enum(BentukKomponen::class)],
            'Komponen.*.Judul' => ['nullable', 'string', 'max:180'],
            'Komponen.*.Lebar' => [
                'nullable', 'integer',
                'min:'.LayananDasbor::LEBAR_MIN,
                'max:'.LayananDasbor::LEBAR_MAKS,
            ],
        ];
    }
}
