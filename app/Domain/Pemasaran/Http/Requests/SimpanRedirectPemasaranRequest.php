<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Http\Requests;

use App\Domain\Pemasaran\Application\Services\PencariRedirectPemasaran;
use App\Domain\Pemasaran\Domain\Enums\KodeRedirect;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\RedirectPemasaran;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SimpanRedirectPemasaranRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('platform') !== null;
    }

    protected function prepareForValidation(): void
    {
        $dari = $this->input('Dari');

        if (is_string($dari)) {
            $this->merge(['Dari' => PencariRedirectPemasaran::normalkan($dari)]);
        }
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $redirect = $this->route('redirect');
        $id = $redirect instanceof RedirectPemasaran ? $redirect->getKey() : null;

        return [
            'Dari' => [
                'required',
                'string',
                'max:500',
                Rule::unique('RedirectPemasaran', 'Dari')->ignore($id, 'Id'),
            ],
            // Wajib kecuali 410, yang menyatakan sumber daya hilang permanen dan memang tidak punya tujuan.
            'Ke' => [
                Rule::requiredIf(fn (): bool => $this->input('Kode') !== KodeRedirect::Hilang->value),
                'nullable',
                'string',
                'max:500',
            ],
            'Kode' => ['required', Rule::enum(KodeRedirect::class)],
            'Aktif' => ['boolean'],
            'Catatan' => ['nullable', 'string', 'max:500'],
        ];
    }
}
