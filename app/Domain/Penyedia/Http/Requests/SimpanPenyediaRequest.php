<?php

declare(strict_types=1);

namespace App\Domain\Penyedia\Http\Requests;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Penyedia\Infrastructure\Persistence\Models\Penyedia;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SimpanPenyediaRequest extends FormRequest
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
        /** @var Penyedia|null $penyedia */
        $penyedia = $this->route('penyedia');
        $penyediaId = $penyedia?->Id;

        return [
            'Kode' => ['required', 'string', 'max:60',
                Rule::unique('Penyedia', 'Kode')->where(fn ($q) => $q->where('OrganisasiId', $organisasiId))->whereNull('DihapusPada')->ignore($penyediaId, 'Id')],
            'Nama' => ['required', 'string', 'max:200'],
            'NamaLegal' => ['nullable', 'string', 'max:240'],
            'NomorIdentitasPajak' => ['nullable', 'string', 'max:100'],
            'Email' => ['nullable', 'email', 'max:180'],
            'Telepon' => ['nullable', 'string', 'max:60'],
            'Website' => ['nullable', 'url', 'max:255'],
            'Alamat' => ['nullable', 'string'],
            'Kota' => ['nullable', 'string', 'max:120'],
            'Provinsi' => ['nullable', 'string', 'max:120'],
            'Negara' => ['nullable', 'string', 'max:100'],
            'Status' => ['required', 'string', Rule::in([Penyedia::STATUS_AKTIF, Penyedia::STATUS_NONAKTIF])],
        ];
    }
}
