<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanPesananPembelianRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'TanggalPesanan' => ['required', 'date'],
            'TanggalKirimRencana' => ['nullable', 'date', 'after_or_equal:TanggalPesanan'],
            'Catatan' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
