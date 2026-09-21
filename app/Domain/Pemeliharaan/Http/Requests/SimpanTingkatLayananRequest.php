<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Http\Requests;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Notifikasi\Domain\Enums\KanalNotifikasi;
use App\Domain\Notifikasi\Domain\Enums\PemicuEskalasiTingkatLayanan;
use App\Domain\Pemeliharaan\Domain\Enums\PrioritasKeluhan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\TingkatLayanan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class SimpanTingkatLayananRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $organisasiId = app(KonteksOrganisasi::class)->wajibId();
        /** @var TingkatLayanan|null $tingkatLayanan */
        $tingkatLayanan = $this->route('tingkatLayanan');

        return [
            'Kode' => ['required', 'string', 'max:60', Rule::unique('TingkatLayanan', 'Kode')->where(fn ($query) => $query->where('OrganisasiId', $organisasiId))->ignore($tingkatLayanan?->Id, 'Id')],
            'Nama' => ['required', 'string', 'max:160'],
            'Deskripsi' => ['nullable', 'string'],
            'HariKerja' => ['required', 'array', 'min:1'],
            'HariKerja.*' => ['required', 'integer', 'between:1,7', 'distinct'],
            'JamKerjaMulai' => ['required', 'date_format:H:i'],
            'JamKerjaSelesai' => ['required', 'date_format:H:i', 'after:JamKerjaMulai'],
            'MemperhitungkanHariLibur' => ['required', 'boolean'],
            'Aktif' => ['required', 'boolean'],
            'Aturan' => ['required', 'array', 'min:1'],
            'Aturan.*.Prioritas' => ['required', Rule::enum(PrioritasKeluhan::class), 'distinct'],
            'Aturan.*.MenitRespons' => ['nullable', 'integer', 'min:1', 'max:525600'],
            'Aturan.*.MenitPenyelesaian' => ['nullable', 'integer', 'min:1', 'max:525600'],
            'Aturan.*.MenghitungJamKerja' => ['required', 'boolean'],
            'Eskalasi' => ['sometimes', 'array'],
            'Eskalasi.*.Tahap' => ['required', 'integer', 'min:1', 'distinct'],
            'Eskalasi.*.Pemicu' => ['required', Rule::enum(PemicuEskalasiTingkatLayanan::class)],
            'Eskalasi.*.SetelahMenit' => ['required', 'integer', 'min:0', 'max:525600'],
            'Eskalasi.*.PeranId' => ['nullable', 'string', Rule::exists('Peran', 'Id')->where(fn ($query) => $query->where('OrganisasiId', $organisasiId))],
            'Eskalasi.*.PenggunaId' => ['nullable', 'string', Rule::exists('Pengguna', 'Id')->where(fn ($query) => $query->where('OrganisasiId', $organisasiId))],
            'Eskalasi.*.Kanal' => ['required', 'array', 'min:1'],
            'Eskalasi.*.Kanal.*' => ['required', Rule::enum(KanalNotifikasi::class), 'distinct'],
            'Eskalasi.*.Aktif' => ['required', 'boolean'],
        ];
    }

    /** @return array<int, callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            foreach ((array) $this->input('Aturan', []) as $indeks => $aturan) {
                $respons = $aturan['MenitRespons'] ?? null;
                $penyelesaian = $aturan['MenitPenyelesaian'] ?? null;
                if ($respons === null && $penyelesaian === null) {
                    $validator->errors()->add("Aturan.{$indeks}.MenitRespons", 'Target respons atau penyelesaian wajib diisi.');
                }
                if ($respons !== null && $penyelesaian !== null && (int) $penyelesaian < (int) $respons) {
                    $validator->errors()->add("Aturan.{$indeks}.MenitPenyelesaian", 'Target penyelesaian tidak boleh lebih singkat dari target respons.');
                }
            }

            foreach ((array) $this->input('Eskalasi', []) as $indeks => $eskalasi) {
                if (empty($eskalasi['PeranId']) && empty($eskalasi['PenggunaId'])) {
                    $validator->errors()->add("Eskalasi.{$indeks}.PeranId", 'Pilih peran atau pengguna penerima eskalasi.');
                }
            }
        }];
    }
}
