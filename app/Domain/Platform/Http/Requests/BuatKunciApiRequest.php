<?php

declare(strict_types=1);

namespace App\Domain\Platform\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class BuatKunciApiRequest extends FormRequest
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
            'Nama' => ['required', 'string', 'max:150'],
            'Cakupan' => ['nullable', 'array'],
            'Cakupan.*' => ['string', Rule::exists('Izin', 'Kode')],
            'KadaluarsaPada' => ['nullable', 'date', 'after:now'],
            'AlamatIpDiizinkan' => ['nullable', 'array'],
            'AlamatIpDiizinkan.*' => ['ip'],
        ];
    }
}
