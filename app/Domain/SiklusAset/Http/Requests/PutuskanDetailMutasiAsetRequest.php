<?php

declare(strict_types=1);

namespace App\Domain\SiklusAset\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class PutuskanDetailMutasiAsetRequest extends FormRequest
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
            'Disetujui' => ['required', 'boolean'],
            'AlasanPenolakan' => ['nullable', 'string', 'max:500', 'required_if:Disetujui,false'],
        ];
    }
}
