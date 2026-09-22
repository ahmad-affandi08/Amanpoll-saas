<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Http\Requests;

use App\Domain\Pemasaran\Infrastructure\Persistence\Models\KontenSosial;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SimpanKontenSosialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('platform') !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $konten = $this->route('konten');
        $id = $konten instanceof KontenSosial ? $konten->getKey() : null;

        return [
            'Kode' => ['required', 'string', 'max:80', Rule::unique('KontenSosial', 'Kode')->ignore($id, 'Id')],
            'Judul' => ['required', 'string', 'max:190'],
            'Ringkasan' => ['nullable', 'string', 'max:500'],
            'MediaUrl' => ['nullable', 'url', 'max:500'],
            'HalamanId' => ['nullable', 'string', Rule::exists('HalamanPemasaran', 'Id')],
            'KampanyeId' => ['nullable', 'string', Rule::exists('Kampanye', 'Id')],
        ];
    }
}
