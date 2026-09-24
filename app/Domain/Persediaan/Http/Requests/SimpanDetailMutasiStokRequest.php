<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Http\Requests;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\MutasiStok;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SimpanDetailMutasiStokRequest extends FormRequest
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
        /** @var MutasiStok|null $mutasiStok */
        $mutasiStok = $this->route('mutasiStok');

        return [
            'SukuCadangId' => ['required', 'string',
                Rule::exists('SukuCadang', 'Id')->where(fn ($q) => $q->where('OrganisasiId', $organisasiId)->whereNull('DihapusPada'))],
            'KelompokSukuCadangId' => ['nullable', 'string',
                Rule::exists('KelompokSukuCadang', 'Id')->where(fn ($q) => $q->where('OrganisasiId', $organisasiId))],
            'Jumlah' => ['required', 'numeric'],
            'HargaSatuan' => ['nullable', 'numeric', 'min:0'],
            // Rak harus milik gudang mutasinya sendiri. Gudang itu sudah diperiksa terlihat
            // (policy `update`), jadi rak gudang lain -- termasuk gudang di luar lingkup -- ditolak.
            'LokasiGudangAsalId' => ['nullable', 'string',
                Rule::exists('LokasiGudang', 'Id')->where(fn ($q) => $q->where('OrganisasiId', $organisasiId)->where('GudangId', $mutasiStok?->GudangAsalId))],
            'LokasiGudangTujuanId' => ['nullable', 'string',
                Rule::exists('LokasiGudang', 'Id')->where(fn ($q) => $q->where('OrganisasiId', $organisasiId)->where('GudangId', $mutasiStok?->GudangTujuanId))],
        ];
    }
}
