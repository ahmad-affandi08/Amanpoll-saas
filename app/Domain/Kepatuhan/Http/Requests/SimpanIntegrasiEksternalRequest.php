<?php

declare(strict_types=1);

namespace App\Domain\Kepatuhan\Http\Requests;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Kepatuhan\Infrastructure\Persistence\Models\IntegrasiEksternal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SimpanIntegrasiEksternalRequest extends FormRequest
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
        $organisasiId = app(KonteksOrganisasi::class)->wajibId();
        $integrasi = $this->route('integrasi');

        return [
            'Kode' => [
                'required', 'string', 'max:80',
                Rule::unique('IntegrasiEksternal', 'Kode')
                    ->where('OrganisasiId', $organisasiId)
                    ->ignore($integrasi instanceof IntegrasiEksternal ? $integrasi->Id : null, 'Id'),
            ],
            'Nama' => ['required', 'string', 'max:180'],
            'Jenis' => ['required', 'string', 'max:80'],
            'UrlDasar' => ['nullable', 'url', 'max:2000'],
            'MetodeAutentikasi' => ['nullable', Rule::in(['Bearer', 'ApiKey', 'Basic', 'TanpaAutentikasi'])],
            'Konfigurasi' => ['nullable', 'array'],
            'Konfigurasi.*' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
