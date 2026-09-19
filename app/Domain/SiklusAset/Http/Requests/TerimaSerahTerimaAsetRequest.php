<?php

declare(strict_types=1);

namespace App\Domain\SiklusAset\Http\Requests;

use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class TerimaSerahTerimaAsetRequest extends FormRequest
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
            'Detail' => ['required', 'array', 'min:1'],
            'Detail.*.AsetId' => ['required', 'string'],
            'Detail.*.KondisiSaatDiterima' => ['required', 'string', Rule::in([Aset::KONDISI_BAIK, Aset::KONDISI_PERLU_PERHATIAN, Aset::KONDISI_RUSAK])],
        ];
    }
}
