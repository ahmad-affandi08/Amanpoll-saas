<?php

declare(strict_types=1);

namespace App\Domain\Penyedia\Http\Requests;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Penyedia\Infrastructure\Persistence\Models\KategoriPenyedia;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SimpanKategoriPenyediaRequest extends FormRequest
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
        /** @var KategoriPenyedia|null $kategoriPenyedia */
        $kategoriPenyedia = $this->route('kategoriPenyedia');
        $kategoriPenyediaId = $kategoriPenyedia?->Id;

        return [
            'Kode' => ['required', 'string', 'max:50',
                Rule::unique('KategoriPenyedia', 'Kode')->where(fn ($q) => $q->where('OrganisasiId', $organisasiId))->ignore($kategoriPenyediaId, 'Id')],
            'Nama' => ['required', 'string', 'max:120'],
        ];
    }
}
