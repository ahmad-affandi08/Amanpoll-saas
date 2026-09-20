<?php

declare(strict_types=1);

namespace App\Domain\PreventifInspeksi\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanButirTemplatDaftarPeriksaRequest extends FormRequest
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
            'Urutan' => ['nullable', 'integer', 'min:0'],
            'Kode' => ['nullable', 'string', 'max:80'],
            'Pertanyaan' => ['required', 'string'],
            'TipeJawaban' => ['required', 'string', 'in:Teks,Angka,Pilihan,YaTidak,Foto'],
            'Satuan' => ['nullable', 'string', 'max:50'],
            'Wajib' => ['nullable', 'boolean'],
            'NilaiMinimum' => ['nullable', 'numeric'],
            'NilaiMaksimum' => ['nullable', 'numeric'],
            'Pilihan' => ['nullable', 'array'],
            'BuktiFotoWajib' => ['nullable', 'boolean'],
            'MemicuTemuanJika' => ['nullable', 'array'],
        ];
    }
}
