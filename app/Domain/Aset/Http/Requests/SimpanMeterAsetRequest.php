<?php

declare(strict_types=1);

namespace App\Domain\Aset\Http\Requests;

use App\Domain\Aset\Infrastructure\Persistence\Models\MeterAset;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SimpanMeterAsetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'Nama' => ['required', 'string', 'max:120'],
            'Satuan' => ['required', 'string', 'max:50'],
            'Jenis' => ['required', 'string', Rule::in([MeterAset::JENIS_KUMULATIF, MeterAset::JENIS_NON_KUMULATIF])],
            'NilaiAwal' => ['nullable', 'numeric', 'min:0'],
            'Aktif' => ['boolean'],
        ];
    }
}
