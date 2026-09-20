<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Http\Requests;

use App\Domain\Pemeliharaan\Domain\Enums\StatusPerintahKerja;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UbahStatusPerintahKerjaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'Status' => ['required', Rule::enum(StatusPerintahKerja::class)],
            'Catatan' => [Rule::requiredIf(in_array($this->input('Status'), ['Dibatalkan', 'Dikerjakan'], true)), 'nullable', 'string', 'max:2000'],
            'RingkasanPenyelesaian' => [Rule::requiredIf($this->input('Status') === StatusPerintahKerja::MenungguVerifikasi->value), 'nullable', 'string', 'max:10000'],
            'Versi' => ['required', 'integer', 'min:1'],
        ];
    }
}
