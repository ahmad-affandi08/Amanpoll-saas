<?php

declare(strict_types=1);

namespace App\Domain\Kodefikasi\Http\Requests;

use App\Domain\Kodefikasi\Domain\Enums\StandarKodefikasi;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ImporKatalogKodeBarangRequest extends FormRequest
{
    private const UKURAN_MAKS_KB = 20480;

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'Standar' => ['required', Rule::enum(StandarKodefikasi::class)],
            // `mimes` membaca isi berkasnya, bukan ekstensi yang diberi pengunggah.
            'Berkas' => ['required', 'file', 'mimes:csv,txt', 'max:'.self::UKURAN_MAKS_KB],
        ];
    }
}
