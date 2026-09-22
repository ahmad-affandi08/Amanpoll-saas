<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Http\Requests;

use App\Domain\Pemasaran\Domain\KatalogKonfigurasiPemasaran;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SimpanKonfigurasiPemasaranRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('platform') !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'Kunci' => ['required', 'string', Rule::in(KatalogKonfigurasiPemasaran::kunci())],
            // Bentuk nilainya berbeda per kunci — angka, teks, atau peta bobot
            // skor — jadi yang ditegakkan di sini hanya bahwa kuncinya dikenal.
            'Nilai' => ['required'],
        ];
    }
}
