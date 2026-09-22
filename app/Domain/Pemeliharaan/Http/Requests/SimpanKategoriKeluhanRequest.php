<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Http\Requests;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Pemeliharaan\Domain\Enums\PrioritasKeluhan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\KategoriKeluhan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SimpanKategoriKeluhanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $organisasiId = app(KonteksOrganisasi::class)->wajibId();
        /** @var KategoriKeluhan|null $kategoriKeluhan */
        $kategoriKeluhan = $this->route('kategoriKeluhan');

        return [
            'IndukId' => ['nullable', 'string', Rule::notIn([$kategoriKeluhan?->Id]), Rule::exists('KategoriKeluhan', 'Id')->where(fn ($query) => $query->where('OrganisasiId', $organisasiId))],
            'Kode' => ['nullable', 'string', 'max:60', Rule::unique('KategoriKeluhan', 'Kode')->where(fn ($query) => $query->where('OrganisasiId', $organisasiId))->ignore($kategoriKeluhan?->Id, 'Id')],
            'Nama' => ['required', 'string', 'max:160'],
            'TingkatLayananId' => ['nullable', 'string', Rule::exists('TingkatLayanan', 'Id')->where(fn ($query) => $query->where('OrganisasiId', $organisasiId)->where('Aktif', true))],
            'PrioritasBawaan' => ['required', Rule::enum(PrioritasKeluhan::class)],
            'AsetWajib' => ['required', 'boolean'],
            'PeranPenanggungJawabId' => ['nullable', 'string', Rule::exists('Peran', 'Id')->where(fn ($query) => $query->where('OrganisasiId', $organisasiId))],
            'Aktif' => ['required', 'boolean'],
        ];
    }
}
