<?php

declare(strict_types=1);

namespace App\Domain\Kalibrasi\Http\Requests;

use App\Core\Organisasi\KonteksOrganisasi;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SimpanTitikUkurKalibrasiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Setiap rujukan dibatasi pada organisasi pemanggil.
     *
     * Tanpa itu, ID milik organisasi lain lolos validasi dan tersimpan apa
     * adanya: kalibrasi organisasi A dapat menunjuk penyedia, perintah kerja,
     * atau pelaksana milik organisasi B. ULID memang sukar ditebak, tetapi
     * integritas tenant tidak boleh bergantung pada itu.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $organisasiId = app(KonteksOrganisasi::class)->id();

        return [
            'JenisKalibrasiId' => ['nullable', 'string', 'max:26',
                Rule::exists('JenisKalibrasi', 'Id')->where(fn ($q) => $q->where('OrganisasiId', $organisasiId))],
            'KategoriAsetId' => ['nullable', 'string', 'max:26',
                Rule::exists('KategoriAset', 'Id')->where(fn ($q) => $q->where('OrganisasiId', $organisasiId)->whereNull('DihapusPada'))],
            'Nama' => ['required', 'string', 'max:160'],
            'Satuan' => ['nullable', 'string', 'max:50'],
            'NilaiReferensi' => ['nullable', 'numeric'],
            'ToleransiMinus' => ['nullable', 'numeric', 'min:0'],
            'ToleransiPlus' => ['nullable', 'numeric', 'min:0'],
            'Urutan' => ['nullable', 'integer'],
            'Aktif' => ['sometimes', 'boolean'],
        ];
    }
}
