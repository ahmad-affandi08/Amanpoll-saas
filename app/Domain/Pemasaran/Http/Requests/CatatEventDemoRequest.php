<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Http\Requests;

use App\Domain\Pemasaran\Domain\Enums\JenisEventDemo;
use App\Domain\Pemasaran\Domain\Enums\ModulDemo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CatatEventDemoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'Jenis' => ['required', Rule::enum(JenisEventDemo::class)],
            'Modul' => ['nullable', Rule::enum(ModulDemo::class)],
            'Rincian' => ['nullable', 'array'],
            'Rincian.*' => ['nullable', 'string', 'max:200'],
        ];
    }
}
