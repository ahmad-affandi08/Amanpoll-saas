<?php

declare(strict_types=1);

namespace App\Domain\Aset\Http\Requests;

use App\Domain\Platform\Http\Requests\UnitPengelolaSah;
use Illuminate\Foundation\Http\FormRequest;

/** Ubah massal unit pengelola aset dari pilihan di daftar aset (PRD 8.21). */
final class AturUnitPengelolaAsetRequest extends FormRequest
{
    /** Sama longgarnya dengan cetak label: inventarisasi awal memang memilih ratusan aset sekaligus. */
    public const MAKS_ASET = CetakLabelAsetRequest::MAKS_LABEL;

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'AsetId' => ['required', 'array', 'min:1', 'max:'.self::MAKS_ASET],
            'AsetId.*' => ['required', 'string', 'ulid', 'distinct'],
            // Kosong berarti melepas unit pengelola dari aset terpilih. Tanpa nilai tersimpan:
            // pilihan massal selalu pilihan baru, jadi unit nonaktif ditolak.
            'UnitPengelolaId' => ['present', ...UnitPengelolaSah::aturan()],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'AsetId.required' => 'Pilih minimal satu aset.',
            'AsetId.max' => 'Sekali ubah paling banyak '.self::MAKS_ASET.' aset.',
        ];
    }
}
