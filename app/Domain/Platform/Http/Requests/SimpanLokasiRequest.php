<?php

declare(strict_types=1);

namespace App\Domain\Platform\Http\Requests;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SimpanLokasiRequest extends FormRequest
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
        /** @var Lokasi|null $lokasi */
        $lokasi = $this->route('lokasi');
        $lokasiId = $lokasi?->Id;

        return [
            'Kode' => ['nullable', 'string', 'max:60',
                Rule::unique('Lokasi', 'Kode')->where(fn ($q) => $q->where('OrganisasiId', $organisasiId))->whereNull('DihapusPada')->ignore($lokasiId, 'Id')],
            'Nama' => ['required', 'string', 'max:180'],
            'UnitOrganisasiId' => ['nullable', 'string',
                Rule::exists('UnitOrganisasi', 'Id')->where(fn ($q) => $q->where('OrganisasiId', $organisasiId)->whereNull('DihapusPada'))],
            'KategoriLokasiId' => ['nullable', 'string',
                Rule::exists('KategoriLokasi', 'Id')->where(fn ($q) => $q->where('OrganisasiId', $organisasiId)->whereNull('DihapusPada'))],
            'IndukId' => ['nullable', 'string',
                Rule::exists('Lokasi', 'Id')->where(fn ($q) => $q->where('OrganisasiId', $organisasiId)->whereNull('DihapusPada'))],
            'Alamat' => ['nullable', 'string'],
            'Lantai' => ['nullable', 'string', 'max:30'],
            'Latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'Longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'ZonaWaktu' => ['nullable', 'string', Rule::in(timezone_identifiers_list())],
            'Status' => ['required', 'string', Rule::in(['Aktif', 'Nonaktif'])],
        ];
    }
}
