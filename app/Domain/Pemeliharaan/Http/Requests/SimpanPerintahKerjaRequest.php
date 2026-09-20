<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Http\Requests;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Pemeliharaan\Domain\Enums\PrioritasKeluhan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SimpanPerintahKerjaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $organisasiId = app(KonteksOrganisasi::class)->wajibId();
        $adaKeluhan = filled($this->input('KeluhanId'));

        return [
            'KeluhanId' => ['nullable', 'string', Rule::exists('Keluhan', 'Id')->where(fn ($query) => $query->where('OrganisasiId', $organisasiId)->whereNull('DihapusPada'))],
            'Jenis' => ['required', Rule::in(['Korektif', 'Preventif', 'Inspeksi', 'Kalibrasi', 'Umum', 'Vendor'])],
            'Judul' => [Rule::requiredIf(! $adaKeluhan), 'nullable', 'string', 'max:220'],
            'Deskripsi' => ['nullable', 'string', 'max:10000'],
            'Prioritas' => ['required', Rule::enum(PrioritasKeluhan::class)],
            'LokasiId' => [Rule::requiredIf(! $adaKeluhan), 'nullable', 'string', Rule::exists('Lokasi', 'Id')->where(fn ($query) => $query->where('OrganisasiId', $organisasiId))],
            'UnitOrganisasiId' => ['nullable', 'string', Rule::exists('UnitOrganisasi', 'Id')->where(fn ($query) => $query->where('OrganisasiId', $organisasiId))],
            'DijadwalkanMulaiPada' => ['nullable', 'date'],
            'DijadwalkanSelesaiPada' => ['nullable', 'date', 'after_or_equal:DijadwalkanMulaiPada'],
            'MembutuhkanWaktuHenti' => ['required', 'boolean'],
            'MembutuhkanPersetujuan' => ['required', 'boolean'],
            'AsetIds' => [Rule::requiredIf(! $adaKeluhan), 'array', 'min:1'],
            'AsetIds.*' => ['string', 'distinct', Rule::exists('Aset', 'Id')->where(fn ($query) => $query->where('OrganisasiId', $organisasiId)->whereNull('DihapusPada'))],
        ];
    }
}
