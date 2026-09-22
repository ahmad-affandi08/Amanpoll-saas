<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Http\Requests;

use App\Domain\Pemasaran\Domain\Enums\JenisBlokHalaman;
use App\Domain\Pemasaran\Domain\Enums\SiklusHarga;
use App\Domain\Pemasaran\Domain\Enums\TipeHalamanPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\HalamanPemasaran;
use Illuminate\Contracts\Validation\Validator;
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

            // Blok harga hanya menyebut kode paket; angkanya datang dari domain Langganan.
            'Blok.*.Isi.siklus' => ['nullable', Rule::enum(SiklusHarga::class)],
            'Blok.*.Isi.paket' => ['nullable', 'array'],
            'Blok.*.Isi.paket.*.kode' => ['nullable', 'string', 'exists:PaketLangganan,Kode'],
            'Blok.*.Isi.paket.*.badge' => ['nullable', 'string', 'max:40'],
            'Blok.*.Isi.paket.*.disorot' => ['nullable', 'boolean'],
            'Blok.*.Isi.paket.*.ringkasan' => ['nullable', 'string', 'max:190'],
            'Blok.*.Isi.paket.*.ctaTeks' => ['nullable', 'string', 'max:60'],
            'Blok.*.Isi.paket.*.ctaUrl' => ['nullable', 'string', 'max:500'],
        ];
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                foreach ($this->blokHarga() as $urutan => $isi) {
                    $this->periksaSorotan($validator, $urutan, $isi);
                }
            },
        ];
    }

    /**
     * Sorotan menandai satu paket yang direkomendasikan; dua sorotan berarti
     * tidak ada yang direkomendasikan.
     *
     * @param  array<string, mixed>  $isi
     */
    private function periksaSorotan(Validator $validator, int $urutan, array $isi): void
    {
        $paket = $isi['paket'] ?? [];

        if (! is_array($paket)) {
            return;
        }

        $disorot = array_filter(
            $paket,
            fn (mixed $satu): bool => is_array($satu) && (bool) ($satu['disorot'] ?? false),
        );

        if (count($disorot) > 1) {
            $validator->errors()->add(
                "Blok.{$urutan}.Isi.paket",
                'Hanya satu paket yang boleh disorot pada satu blok harga.',
            );
        }
    }

    /** @return array<int, array<string, mixed>> */
    private function blokHarga(): array
    {
        $blok = $this->input('Blok');

        if (! is_array($blok)) {
            return [];
        }

        $hasil = [];

        foreach (array_values($blok) as $urutan => $satu) {
            if (is_array($satu)
                && ($satu['Jenis'] ?? null) === JenisBlokHalaman::Harga->value
                && is_array($satu['Isi'] ?? null)
            ) {
                $hasil[$urutan] = $satu['Isi'];
            }
        }

        return $hasil;
    }
}
