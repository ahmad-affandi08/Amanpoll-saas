<?php

declare(strict_types=1);

namespace App\Domain\Persetujuan\Http\Requests;

use App\Core\Organisasi\KonteksOrganisasi;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SimpanTahapPersetujuanRequest extends FormRequest
{
    public const JENIS_PENYETUJU_DIIZINKAN = ['Pengguna', 'Peran', 'Unit'];

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** Ambang yang dikosongkan berarti tahap tanpa syarat, bukan ambang nol. */
    protected function prepareForValidation(): void
    {
        if ($this->has('Kondisi') && blank($this->input('Kondisi.NilaiMinimum'))) {
            $this->merge(['Kondisi' => null]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $organisasiId = app(KonteksOrganisasi::class)->id();

        return [
            'Urutan' => ['required', 'integer', 'min:1'],
            'Nama' => ['required', 'string', 'max:160'],
            'JenisPenyetuju' => ['required', Rule::in(self::JENIS_PENYETUJU_DIIZINKAN)],
            'PeranId' => [
                Rule::requiredIf(fn () => $this->input('JenisPenyetuju') === 'Peran'),
                'nullable', 'string',
                Rule::exists('Peran', 'Id')->where(fn ($q) => $q->where('OrganisasiId', $organisasiId)),
            ],
            'PenggunaId' => [
                Rule::requiredIf(fn () => $this->input('JenisPenyetuju') === 'Pengguna'),
                'nullable', 'string',
                Rule::exists('Pengguna', 'Id')->where(fn ($q) => $q->where('OrganisasiId', $organisasiId)),
            ],
            'JumlahMinimumPenyetuju' => ['required', 'integer', 'min:1'],
            'BolehMenyetujuiSendiri' => ['sometimes', 'boolean'],
            'BatasWaktuMenit' => ['nullable', 'integer', 'min:1'],
            'Kondisi' => ['nullable', 'array:NilaiMinimum'],
            'Kondisi.NilaiMinimum' => ['nullable', 'numeric', 'min:0', 'max:999999999999999'],
        ];
    }
}
