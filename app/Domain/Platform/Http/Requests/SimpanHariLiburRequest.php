<?php

declare(strict_types=1);

namespace App\Domain\Platform\Http\Requests;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\HariLibur;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SimpanHariLiburRequest extends FormRequest
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
        /** @var HariLibur|null $hariLibur */
        $hariLibur = $this->route('hariLibur');

        return [
            'Tanggal' => ['required', 'date',
                Rule::unique('HariLibur', 'Tanggal')
                    ->where(fn ($q) => $q
                        ->where('OrganisasiId', $organisasiId)
                        ->where('LokasiId', $this->input('LokasiId'))
                        ->where('Nama', $this->input('Nama')))
                    ->ignore($hariLibur?->Id, 'Id')],
            'Nama' => ['required', 'string', 'max:180'],
            'BerulangTahunan' => ['required', 'boolean'],
            'LokasiId' => ['nullable', 'string',
                Rule::exists('Lokasi', 'Id')->where(fn ($q) => $q->where('OrganisasiId', $organisasiId)->whereNull('DihapusPada'))],
        ];
    }
}
