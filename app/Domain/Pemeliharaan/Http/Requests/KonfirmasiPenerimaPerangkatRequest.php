<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Tanda tangan penerima tanpa akun di HP teknisi (PRD 8.22, cara 3). `KunciPerangkat`
 * dibuat HP saat tanda tangan disimpan sebagai draf, supaya kiriman ulang antrean
 * offline tidak menggandakan konfirmasi.
 */
final class KonfirmasiPenerimaPerangkatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('web') !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'NamaPenerima' => ['required', 'string', 'max:150'],
            'JabatanPenerima' => ['nullable', 'string', 'max:150'],
            'TandaTangan' => ['required', 'file', 'mimes:png,webp,jpg,jpeg', 'max:512'],
            'KunciPerangkat' => ['nullable', 'string', 'max:64'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['NamaPenerima' => 'nama penerima', 'JabatanPenerima' => 'jabatan penerima', 'TandaTangan' => 'tanda tangan'];
    }
}
