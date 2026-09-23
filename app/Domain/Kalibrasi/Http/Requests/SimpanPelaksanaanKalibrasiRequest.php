<?php

declare(strict_types=1);

namespace App\Domain\Kalibrasi\Http\Requests;

use App\Core\Organisasi\KonteksOrganisasi;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SimpanPelaksanaanKalibrasiRequest extends FormRequest
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
            'Nomor' => ['nullable', 'string', 'max:100'],
            'RencanaKalibrasiId' => ['nullable', 'string', 'max:26',
                Rule::exists('RencanaKalibrasi', 'Id')->where(fn ($q) => $q->where('OrganisasiId', $organisasiId))],
            'JenisKalibrasiId' => ['nullable', 'string', 'max:26',
                Rule::exists('JenisKalibrasi', 'Id')->where(fn ($q) => $q->where('OrganisasiId', $organisasiId))],
            'PenyediaId' => ['nullable', 'string', 'max:26',
                Rule::exists('Penyedia', 'Id')->where(fn ($q) => $q->where('OrganisasiId', $organisasiId)->whereNull('DihapusPada'))],
            'PerintahKerjaId' => ['nullable', 'string', 'max:26',
                Rule::exists('PerintahKerja', 'Id')->where(fn ($q) => $q->where('OrganisasiId', $organisasiId)->whereNull('DihapusPada'))],
            'TanggalKalibrasi' => ['required', 'date'],
            'TanggalBerlakuSampai' => ['nullable', 'date'],
            'Hasil' => ['nullable', 'string', 'max:40'],
            'NomorSertifikat' => ['nullable', 'string', 'max:180'],
            'Laboratorium' => ['nullable', 'string', 'max:200'],
            'KondisiLingkungan' => ['nullable', 'array'],
            'Catatan' => ['nullable', 'string'],
            'DilaksanakanOleh' => ['nullable', 'string', 'max:26',
                Rule::exists('Pengguna', 'Id')->where(fn ($q) => $q->where('OrganisasiId', $organisasiId)->whereNull('DihapusPada'))],
        ];
    }
}
