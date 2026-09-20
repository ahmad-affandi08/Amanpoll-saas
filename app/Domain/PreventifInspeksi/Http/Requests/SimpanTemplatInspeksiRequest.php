<?php

declare(strict_types=1);

namespace App\Domain\PreventifInspeksi\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanTemplatInspeksiRequest extends FormRequest
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
            'Kode' => ['required', 'string', 'max:80'],
            'Nama' => ['required', 'string', 'max:200'],
            'KategoriAsetId' => ['nullable', 'string', 'size:26'],
            'TemplatDaftarPeriksaId' => ['required', 'string', 'size:26'],
            'IntervalHari' => ['required', 'integer', 'min:1'],
            'Aktif' => ['nullable', 'boolean'],
        ];
    }
}
