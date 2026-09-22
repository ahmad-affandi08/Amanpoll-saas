<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Http\Requests;

use App\Domain\Pemasaran\Domain\Enums\JenisBlokHalaman;
use App\Domain\Pemasaran\Domain\Enums\TipeHalamanPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\HalamanPemasaran;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SimpanHalamanPemasaranRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('platform') !== null;
    }

    /** Slug dinormalkan sebelum divalidasi. */
    protected function prepareForValidation(): void
    {
        $slug = $this->input('Slug');

        if (is_string($slug)) {
            $bersih = trim($slug, '/');
            $this->merge(['Slug' => $bersih === '' ? '/' : '/'.$bersih]);
        }
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $halaman = $this->route('halaman');
        $id = $halaman instanceof HalamanPemasaran ? $halaman->getKey() : null;

        return [
            'Slug' => [
                'required',
                'string',
                'max:190',
                'regex:#^/[a-z0-9\-/]*$#',
                Rule::unique('HalamanPemasaran', 'Slug')->ignore($id, 'Id'),
            ],
            'Tipe' => ['required', Rule::enum(TipeHalamanPemasaran::class)],
            'Judul' => ['required', 'string', 'max:190'],
            'Segmen' => ['nullable', 'string', 'max:60'],
            'KampanyeId' => ['nullable', 'string', 'exists:Kampanye,Id'],
            'NoIndex' => ['boolean'],

            'MetaJudul' => ['nullable', 'string', 'max:190'],
            'MetaDeskripsi' => ['nullable', 'string', 'max:500'],
            'Kanonik' => ['nullable', 'url', 'max:500'],
            'OgJudul' => ['nullable', 'string', 'max:190'],
            'OgDeskripsi' => ['nullable', 'string', 'max:500'],
            'OgGambar' => ['nullable', 'url', 'max:500'],
            'SkemaTipe' => ['nullable', 'string', 'max:60'],
            'Catatan' => ['nullable', 'string', 'max:500'],

            'Blok' => ['present', 'array'],
            'Blok.*.Jenis' => ['required', Rule::enum(JenisBlokHalaman::class)],
            'Blok.*.Isi' => ['nullable', 'array'],
            'Blok.*.FormulirKode' => ['nullable', 'string', 'exists:FormulirPemasaran,Kode'],
        ];
    }
}
