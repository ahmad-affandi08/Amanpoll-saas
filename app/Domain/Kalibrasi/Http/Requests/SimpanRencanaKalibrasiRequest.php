<?php

declare(strict_types=1);

namespace App\Domain\Kalibrasi\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanRencanaKalibrasiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'AsetId' => ['required', 'string', 'max:26'],
            'JenisKalibrasiId' => ['nullable', 'string', 'max:26'],
            'PenyediaId' => ['nullable', 'string', 'max:26'],
            'IntervalHari' => ['required', 'integer', 'min:1'],
            'TanggalMulai' => ['required', 'date'],
            'TanggalBerikutnya' => ['nullable', 'date'],
            'PeringatanHariSebelum' => ['nullable', 'integer', 'min:1'],
            'Aktif' => ['sometimes', 'boolean'],
        ];
    }
}
