<?php

declare(strict_types=1);

namespace App\Domain\Sinkronisasi\Http\Requests;

use App\Http\Middleware\ArahkanPenggunaLapangan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UbahTampilanLapanganRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'Tampilan' => [
                'required',
                'string',
                Rule::in([ArahkanPenggunaLapangan::TAMPILAN_LAPANGAN, ArahkanPenggunaLapangan::TAMPILAN_DASBOR]),
            ],
        ];
    }
}
