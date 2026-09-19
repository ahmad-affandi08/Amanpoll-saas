<?php

declare(strict_types=1);

namespace App\Domain\SiklusAset\Http\Requests;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\SiklusAset\Infrastructure\Persistence\Models\PermintaanMutasiAset;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SimpanPermintaanMutasiAsetRequest extends FormRequest
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
            'JenisMutasi' => ['required', 'string', Rule::in([
                PermintaanMutasiAset::JENIS_ANTAR_LOKASI,
                PermintaanMutasiAset::JENIS_ANTAR_UNIT,
                PermintaanMutasiAset::JENIS_PEMINJAMAN,
                PermintaanMutasiAset::JENIS_PENGEMBALIAN,
            ])],
            'UnitAsalId' => ['nullable', 'string', Rule::exists('UnitOrganisasi', 'Id')->where('OrganisasiId', $organisasiId)],
            'UnitTujuanId' => ['nullable', 'string', Rule::exists('UnitOrganisasi', 'Id')->where('OrganisasiId', $organisasiId)],
            'LokasiAsalId' => ['nullable', 'string', Rule::exists('Lokasi', 'Id')->where('OrganisasiId', $organisasiId)],
            'LokasiTujuanId' => ['nullable', 'string', Rule::exists('Lokasi', 'Id')->where('OrganisasiId', $organisasiId)],
            'Alasan' => ['nullable', 'string'],
        ];
    }
}
