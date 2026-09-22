<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanLangkahSequenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('platform') !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'TemplateEmailPemasaranId' => ['required', 'string', 'exists:TemplateEmailPemasaran,Id'],
            'Urutan' => ['required', 'integer', 'between:0,99'],
            // Hari ke-0 berarti berangkat saat pendaftaran; batas atas menjaga jadwal tetap masuk akal.
            'HariKe' => ['required', 'integer', 'between:0,365'],
            'Aktif' => ['boolean'],
        ];
    }
}
