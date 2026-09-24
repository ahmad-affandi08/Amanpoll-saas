<?php

declare(strict_types=1);

namespace App\Domain\IntegrasiAudit\Http\Requests;

use App\Shared\Infrastructure\Validasi\UrlKeluarPublik;
use Illuminate\Foundation\Http\FormRequest;

final class SimpanPanggilanBalikWebRequest extends FormRequest
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
            'Nama' => ['required', 'string', 'max:160'],
            // Tujuan harus host publik; pengiriman memeriksanya lagi karena DNS dapat berubah.
            'Url' => ['bail', 'required', 'url', 'max:2000', new UrlKeluarPublik],
            // Rahasia wajib saat membuat; saat mengubah, kosong berarti tetap memakai yang lama.
            'Rahasia' => [$this->isMethod('POST') ? 'required' : 'nullable', 'string', 'min:16', 'max:200'],
            'Peristiwa' => ['required', 'array', 'min:1'],
            'Peristiwa.*' => ['required', 'string', 'max:120'],
            'Aktif' => ['required', 'boolean'],
        ];
    }
}
