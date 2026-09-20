<?php

declare(strict_types=1);

namespace App\Domain\PreventifInspeksi\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanRencanaPemeliharaanAsetRequest extends FormRequest
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
            'AsetId' => ['required', 'string', 'size:26'],
            'TanggalMulai' => ['nullable', 'date'],
            'TanggalBerikutnya' => ['nullable', 'date'],
        ];
    }
}
