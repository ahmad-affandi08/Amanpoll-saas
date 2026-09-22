<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Http\Requests;

use App\Domain\Pemasaran\Domain\Enums\StatusTrial;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UbahStatusTrialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('platform') !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            // Perpanjangan punya rutenya sendiri karena ia juga menggeser tanggal.
            'Status' => ['required', Rule::enum(StatusTrial::class)->except([StatusTrial::Diperpanjang])],
            'Alasan' => ['nullable', 'string', 'max:500'],
        ];
    }
}
