<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ImporProspekRequest extends FormRequest
{
    private const UKURAN_MAKS_KB = 5120;

    public function authorize(): bool
    {
        return $this->user('platform') !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            // `mimes` membaca isi berkasnya, bukan ekstensi yang diberi pengunggah.
            'Berkas' => ['required', 'file', 'mimes:csv,txt', 'max:'.self::UKURAN_MAKS_KB],
        ];
    }
}
