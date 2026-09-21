<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Http\Requests;

use App\Domain\PerencanaanPengadaan\Domain\Enums\PrioritasUsulanAset;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SimpanPenilaianUsulanAsetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'Kriteria' => ['required', 'string', 'max:160'],
            'Bobot' => ['required', 'numeric', 'decimal:0,4', 'min:0.0001', 'max:100'],
            'Nilai' => ['required', 'numeric', 'decimal:0,4', 'min:0', 'max:100'],
            'Prioritas' => ['nullable', 'string', Rule::enum(PrioritasUsulanAset::class)],
        ];
    }
}
