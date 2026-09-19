<?php

declare(strict_types=1);

namespace App\Domain\Aset\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanNilaiAsetRequest extends FormRequest
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
            'TanggalNilai' => ['required', 'date'],
            'NilaiBuku' => ['required', 'numeric', 'min:0'],
            'AkumulasiPenyusutan' => ['nullable', 'numeric', 'min:0'],
            'BebanPenyusutanPeriode' => ['nullable', 'numeric', 'min:0'],
            'Metode' => ['nullable', 'string', 'max:40'],
        ];
    }
}
