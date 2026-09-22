<?php

declare(strict_types=1);

namespace App\Domain\Platform\Http\Requests;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\KategoriLokasi;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SimpanKategoriLokasiRequest extends FormRequest
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
        /** @var KategoriLokasi|null $kategoriLokasi */
        $kategoriLokasi = $this->route('kategoriLokasi');
        $kategoriLokasiId = $kategoriLokasi?->Id;

        return [
            'Kode' => ['nullable', 'string', 'max:50',
                Rule::unique('KategoriLokasi', 'Kode')->where(fn ($q) => $q->where('OrganisasiId', $organisasiId))->whereNull('DihapusPada')->ignore($kategoriLokasiId, 'Id')],
            'Nama' => ['required', 'string', 'max:120'],
            'Keterangan' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
