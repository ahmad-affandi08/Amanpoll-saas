<?php

declare(strict_types=1);

namespace App\Domain\SiklusAset\Http\Requests;

use App\Core\Organisasi\KonteksOrganisasi;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SimpanSerahTerimaAsetRequest extends FormRequest
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
            'PermintaanMutasiAsetId' => ['nullable', 'string', Rule::exists('PermintaanMutasiAset', 'Id')->where('OrganisasiId', $organisasiId)],
            'Jenis' => ['required', 'string', 'max:60'],
            'PihakMenyerahkan' => ['nullable', 'string', Rule::exists('Pengguna', 'Id')->where('OrganisasiId', $organisasiId)],
            'PihakMenerima' => ['nullable', 'string', Rule::exists('Pengguna', 'Id')->where('OrganisasiId', $organisasiId)],
            'Catatan' => ['nullable', 'string'],
        ];
    }
}
