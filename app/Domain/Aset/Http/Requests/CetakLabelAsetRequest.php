<?php

declare(strict_types=1);

namespace App\Domain\Aset\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CetakLabelAsetRequest extends FormRequest
{
    /**
     * Batas sekali cetak.
     *
     * Lebih longgar daripada tool publik karena pemakainya sudah masuk dan
     * inventarisasi awal memang mencetak ratusan label sekaligus; tetap dipatok
     * supaya satu permintaan tidak menyandera proses PHP di hosting bersama.
     */
    public const MAKS_LABEL = 200;

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'id' => ['required', 'array', 'min:1', 'max:'.self::MAKS_LABEL],
            'id.*' => ['required', 'string', 'ulid'],
        ];
    }
}
