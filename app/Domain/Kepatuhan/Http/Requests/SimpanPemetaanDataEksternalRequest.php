<?php

declare(strict_types=1);

namespace App\Domain\Kepatuhan\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanPemetaanDataEksternalRequest extends FormRequest
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
            'JenisEntitas' => ['required', 'string', 'max:80'],
            'EntitasId' => ['required', 'string', 'size:26'],
            'KodeEksternal' => ['required', 'string', 'max:255'],
            'DataTambahan' => ['nullable', 'array'],
        ];
    }
}
