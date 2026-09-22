<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Http\Requests;

use App\Domain\Pemasaran\Domain\Enums\JenisFieldFormulir;
use App\Domain\Pemasaran\Domain\Enums\SumberProspek;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\FormulirPemasaran;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SimpanFormulirPemasaranRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('platform') !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $formulir = $this->route('formulir');
        $id = $formulir instanceof FormulirPemasaran ? $formulir->getKey() : null;

        return [
            'Kode' => [
                'nullable',
                'string',
                'max:80',
                'regex:/^[a-z0-9\-]+$/',
                Rule::unique('FormulirPemasaran', 'Kode')->ignore($id, 'Id'),
            ],
            'Nama' => ['required', 'string', 'max:190'],
            'PesanSukses' => ['nullable', 'string', 'max:1000'],
            'UrlRedirect' => ['nullable', 'url', 'max:500'],
            'Sumber' => ['required', Rule::enum(SumberProspek::class)],
            'KampanyeId' => ['nullable', 'string', 'exists:Kampanye,Id'],
            'Tag' => ['nullable', 'array'],
            'Tag.*' => ['string', 'max:80'],
            'PemicuOtomasi' => ['nullable', 'string', 'max:120'],
            'UrlWebhook' => ['nullable', 'url', 'max:500'],
            'WajibPersetujuan' => ['boolean'],
            'CaptchaAktif' => ['boolean'],
            'Aktif' => ['boolean'],

            'Field' => ['present', 'array', 'min:1'],
            'Field.*.Kode' => ['required', 'string', 'max:80', 'distinct'],
            'Field.*.Label' => ['required', 'string', 'max:190'],
            'Field.*.Jenis' => ['required', Rule::enum(JenisFieldFormulir::class)],
            'Field.*.Wajib' => ['boolean'],
            'Field.*.Pilihan' => ['nullable', 'array'],
            'Field.*.Pilihan.*' => ['string', 'max:190'],
            'Field.*.Placeholder' => ['nullable', 'string', 'max:190'],
            'Field.*.Bantuan' => ['nullable', 'string', 'max:500'],
        ];
    }
}
