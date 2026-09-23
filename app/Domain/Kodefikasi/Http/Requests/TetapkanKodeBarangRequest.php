<?php

declare(strict_types=1);

namespace App\Domain\Kodefikasi\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class TetapkanKodeBarangRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'AsetId' => ['required', 'string', 'exists:Aset,Id'],
            'KodeBarangId' => ['required', 'string', 'exists:KodeBarang,Id'],
        ];
    }
}
