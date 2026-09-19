<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Http\Requests;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\Gudang;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\LokasiGudang;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * GudangId SENGAJA tidak divalidasi di sini -- ia berasal dari route binding
 * ({gudang} saat membuat, atau LokasiGudang.GudangId yang sudah ada saat
 * mengubah), bukan field yang bisa diedit klien. Konsisten dengan pola
 * LokasiId Aset yang tidak bisa dipindah lewat form edit umum (ADR 0008
 * bagian 5) -- lokasi dalam gudang tidak bisa "dipindah gudang" lewat edit
 * biasa begitu dibuat.
 */
final class SimpanLokasiGudangRequest extends FormRequest
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
        /** @var LokasiGudang|null $lokasiGudang */
        $lokasiGudang = $this->route('lokasiGudang');
        /** @var Gudang|null $gudang */
        $gudang = $this->route('gudang');

        $gudangId = $lokasiGudang !== null ? $lokasiGudang->GudangId : $gudang?->Id;

        return [
            'Kode' => ['required', 'string', 'max:60',
                Rule::unique('LokasiGudang', 'Kode')->where(fn ($q) => $q->where('GudangId', $gudangId))->ignore($lokasiGudang?->Id, 'Id')],
            'Nama' => ['required', 'string', 'max:120'],
            'IndukId' => ['nullable', 'string',
                Rule::exists('LokasiGudang', 'Id')->where(fn ($q) => $q->where('OrganisasiId', $organisasiId))],
        ];
    }
}
