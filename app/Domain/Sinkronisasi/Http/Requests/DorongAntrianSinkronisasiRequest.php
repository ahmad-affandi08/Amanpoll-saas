<?php

declare(strict_types=1);

namespace App\Domain\Sinkronisasi\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Amplop pengiriman antrean offline. */
final class DorongAntrianSinkronisasiRequest extends FormRequest
{
    /** Batas mutasi per pengiriman supaya satu permintaan tetap ringan. */
    public const BATAS_MUTASI = 100;

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'IdentitasPerangkat' => ['required', 'string', 'max:255'],
            'NamaPerangkat' => ['nullable', 'string', 'max:180'],
            'Platform' => ['nullable', 'string', 'max:60'],
            'Mutasi' => ['present', 'array', 'max:'.self::BATAS_MUTASI],
            'Mutasi.*.KunciOperasi' => ['required', 'string', 'max:255'],
            'Mutasi.*.Operasi' => ['required', 'string', 'max:80'],
            'Mutasi.*.EntitasId' => ['nullable', 'string', 'max:26'],
            'Mutasi.*.VersiKlien' => ['nullable', 'integer', 'min:1'],
            'Mutasi.*.MuatanData' => ['required', 'array'],
        ];
    }
}
