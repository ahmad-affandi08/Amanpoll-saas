<?php

declare(strict_types=1);

namespace App\Domain\PreventifInspeksi\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanJawabanDaftarPeriksaRequest extends FormRequest
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
        return [
            'jawaban' => ['required', 'array', 'min:1'],
            'jawaban.*.ButirTemplatDaftarPeriksaId' => ['required', 'string', 'size:26'],
            'jawaban.*.NilaiTeks' => ['nullable', 'string'],
            'jawaban.*.NilaiAngka' => ['nullable', 'numeric'],
            'jawaban.*.NilaiBoolean' => ['nullable', 'boolean'],
            'jawaban.*.NilaiTanggal' => ['nullable', 'date'],
            'jawaban.*.NilaiJson' => ['nullable', 'array'],
            'jawaban.*.Catatan' => ['nullable', 'string'],
        ];
    }
}
