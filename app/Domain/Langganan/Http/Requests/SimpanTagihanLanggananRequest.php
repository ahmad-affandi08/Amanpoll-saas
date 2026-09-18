<?php

declare(strict_types=1);

namespace App\Domain\Langganan\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanTagihanLanggananRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        // Perketat rule sesuai invariant use-case sebelum endpoint diaktifkan.
        return [
            'OrganisasiId' => ['sometimes'],
            'LanggananId' => ['sometimes'],
            'Nomor' => ['sometimes'],
            'PeriodeMulai' => ['sometimes'],
            'PeriodeSelesai' => ['sometimes'],
            'JatuhTempo' => ['sometimes'],
            'Subtotal' => ['sometimes'],
            'Pajak' => ['sometimes'],
            'Total' => ['sometimes'],
            'Status' => ['sometimes'],
        ];
    }
}
