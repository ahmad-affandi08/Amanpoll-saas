<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Http\Requests;

use App\Domain\Pemasaran\Domain\Enums\MetrikTargetKampanye;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SimpanKampanyeTargetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('platform') !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'Target' => ['present', 'array'],
            'Target.*.Metrik' => ['required', Rule::enum(MetrikTargetKampanye::class), 'distinct'],
            'Target.*.Nilai' => ['required', 'numeric', 'min:0', 'max:99999999999.99'],
        ];
    }
}
