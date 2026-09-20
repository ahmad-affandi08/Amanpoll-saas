<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Http\Requests;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Pemeliharaan\Domain\Enums\PrioritasKeluhan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\KategoriKeluhan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class SimpanKeluhanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $organisasiId = app(KonteksOrganisasi::class)->wajibId();

        return [
            'KategoriKeluhanId' => ['required', 'string', Rule::exists('KategoriKeluhan', 'Id')->where(fn ($query) => $query->where('OrganisasiId', $organisasiId)->where('Aktif', true))],
            'AsetId' => ['nullable', 'string', Rule::exists('Aset', 'Id')->where(fn ($query) => $query->where('OrganisasiId', $organisasiId)->whereNull('DihapusPada'))],
            'LokasiId' => ['required', 'string', Rule::exists('Lokasi', 'Id')->where(fn ($query) => $query->where('OrganisasiId', $organisasiId)->whereNull('DihapusPada'))],
            'Judul' => ['required', 'string', 'max:220'],
            'Deskripsi' => ['required', 'string', 'max:10000'],
            'Prioritas' => ['nullable', Rule::enum(PrioritasKeluhan::class)],
            'Lampiran' => ['sometimes', 'array', 'max:5'],
            'Lampiran.*' => ['file', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx,csv,txt', 'max:10240'],
        ];
    }

    /** @return array<int, callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->has('KategoriKeluhanId')) {
                return;
            }

            $kategori = KategoriKeluhan::query()->find($this->input('KategoriKeluhanId'));
            if ($kategori?->AsetWajib && ! $this->filled('AsetId')) {
                $validator->errors()->add('AsetId', 'Aset wajib dipilih untuk kategori keluhan ini.');
            }
        }];
    }
}
