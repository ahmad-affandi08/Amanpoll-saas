<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Http\Requests;

use App\Domain\Pemasaran\Domain\Enums\JenisKontenPemasaran;
use App\Domain\Pemasaran\Domain\Enums\ToolPublik;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\HalamanPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\KontenPemasaran;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SimpanKontenPemasaranRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('platform') !== null;
    }

    /** Slug dikirim sebagai satu ruas; awalan jalurnya datang dari jenis konten. */
    protected function prepareForValidation(): void
    {
        $slug = $this->input('Slug');

        if (is_string($slug)) {
            $this->merge(['Slug' => trim($slug, '/')]);
        }
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'Slug' => ['required', 'string', 'max:120', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'Jenis' => ['required', Rule::enum(JenisKontenPemasaran::class)],
            'Judul' => ['required', 'string', 'max:190'],
            'PenulisNama' => ['nullable', 'string', 'max:120'],
            'KampanyeId' => ['nullable', 'string', 'exists:Kampanye,Id'],
            'NoIndex' => ['boolean'],

            'Ringkasan' => ['nullable', 'string', 'max:500'],
            'IsiMarkdown' => ['required', 'string'],

            'MetaJudul' => ['nullable', 'string', 'max:190'],
            'MetaDeskripsi' => ['nullable', 'string', 'max:500'],
            'Kanonik' => ['nullable', 'url', 'max:500'],
            'OgJudul' => ['nullable', 'string', 'max:190'],
            'OgDeskripsi' => ['nullable', 'string', 'max:500'],
            'OgGambar' => ['nullable', 'url', 'max:500'],
            'SkemaTipe' => ['nullable', 'string', 'max:60'],
            'Catatan' => ['nullable', 'string', 'max:500'],
        ];
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $jalur = $this->jalur();

                if ($this->dipakaiKontenLain($jalur)) {
                    $validator->errors()->add('Slug', "Jalur {$jalur} sudah dipakai konten lain.");

                    return;
                }

                // Rute konten dikenali sebelum penampung halaman, jadi jalur kembar menyembunyikan halamannya.
                if (HalamanPemasaran::query()->where('Slug', $jalur)->exists()) {
                    $validator->errors()->add('Slug', "Jalur {$jalur} sudah dipakai halaman pemasaran.");

                    return;
                }

                if (ToolPublik::menempati($jalur)) {
                    $validator->errors()->add('Slug', "Jalur {$jalur} sudah dipakai tool publik.");
                }
            },
        ];
    }

    private function jalur(): string
    {
        $jenis = JenisKontenPemasaran::from((string) $this->input('Jenis'));

        return $jenis->awalanJalur().'/'.(string) $this->input('Slug');
    }

    private function dipakaiKontenLain(string $jalur): bool
    {
        $konten = $this->route('konten');
        $kueri = KontenPemasaran::query()->where('Slug', $jalur);

        if ($konten instanceof KontenPemasaran) {
            $kueri->where('Id', '!=', $konten->Id);
        }

        return $kueri->exists();
    }
}
