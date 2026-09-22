<?php

declare(strict_types=1);

namespace App\Domain\PreventifInspeksi\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanTemplatDaftarPeriksaRequest extends FormRequest
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
            'Kode' => ['nullable', 'string', 'max:80'],
            'Nama' => ['required', 'string', 'max:200'],
            'Jenis' => ['nullable', 'string', 'max:50'],
            'KategoriAsetId' => ['nullable', 'string', 'size:26'],
            'ModelAsetId' => ['nullable', 'string', 'size:26'],
            'VersiTemplat' => ['nullable', 'integer', 'min:1'],
            'Aktif' => ['nullable', 'boolean'],
        ];
    }
}
