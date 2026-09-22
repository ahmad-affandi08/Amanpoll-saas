<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Http\Requests;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\KategoriSukuCadang;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SimpanKategoriSukuCadangRequest extends FormRequest
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
        /** @var KategoriSukuCadang|null $kategoriSukuCadang */
        $kategoriSukuCadang = $this->route('kategoriSukuCadang');

        return [
            'Kode' => ['nullable', 'string', 'max:60',
                Rule::unique('KategoriSukuCadang', 'Kode')->where(fn ($q) => $q->where('OrganisasiId', $organisasiId))->ignore($kategoriSukuCadang?->Id, 'Id')],
            'Nama' => ['required', 'string', 'max:160'],
            'IndukId' => ['nullable', 'string',
                Rule::exists('KategoriSukuCadang', 'Id')->where(fn ($q) => $q->where('OrganisasiId', $organisasiId))],
        ];
    }
}
