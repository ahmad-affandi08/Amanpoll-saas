<?php

declare(strict_types=1);

namespace App\Domain\Kalibrasi\Http\Requests;

use App\Core\Organisasi\KonteksOrganisasi;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SimpanRencanaKalibrasiRequest extends FormRequest
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
            'AsetId' => ['required', 'string', 'max:26',
                Rule::exists('Aset', 'Id')->where(fn ($q) => $q->where('OrganisasiId', $organisasiId)->whereNull('DihapusPada'))],
            'JenisKalibrasiId' => ['nullable', 'string', 'max:26',
                Rule::exists('JenisKalibrasi', 'Id')->where(fn ($q) => $q->where('OrganisasiId', $organisasiId))],
            'PenyediaId' => ['nullable', 'string', 'max:26',
                Rule::exists('Penyedia', 'Id')->where(fn ($q) => $q->where('OrganisasiId', $organisasiId)->whereNull('DihapusPada'))],
            'IntervalHari' => ['required', 'integer', 'min:1'],
            'TanggalMulai' => ['required', 'date'],
            'TanggalBerikutnya' => ['nullable', 'date'],
            'PeringatanHariSebelum' => ['nullable', 'integer', 'min:1'],
            'Aktif' => ['sometimes', 'boolean'],
        ];
    }
}
