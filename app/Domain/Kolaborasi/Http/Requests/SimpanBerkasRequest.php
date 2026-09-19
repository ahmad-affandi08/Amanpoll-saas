<?php

declare(strict_types=1);

namespace App\Domain\Kolaborasi\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanBerkasRequest extends FormRequest
{
    private const MIME_DIIZINKAN = 'pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx,csv,txt';
    private const UKURAN_MAKS_KB = 10240;

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
            'Berkas' => ['required', 'file', 'mimes:'.self::MIME_DIIZINKAN, 'max:'.self::UKURAN_MAKS_KB],
            'JenisEntitas' => ['nullable', 'string', 'required_with:EntitasId'],
            'EntitasId' => ['nullable', 'string', 'required_with:JenisEntitas'],
            'Kategori' => ['nullable', 'string', 'max:80'],
            'Keterangan' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
