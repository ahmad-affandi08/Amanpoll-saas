<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Http\Requests;

use App\Domain\Pemasaran\Infrastructure\Persistence\Models\ProgramPartner;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SimpanProgramPartnerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('platform') !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $program = $this->route('programPartner');
        $id = $program instanceof ProgramPartner ? $program->getKey() : null;

        return [
            'Kode' => [
                'required',
                'string',
                'max:80',
                'regex:/^[a-z0-9-]+$/',
                Rule::unique('ProgramPartner', 'Kode')->ignore($id, 'Id'),
            ],
            'Nama' => ['required', 'string', 'max:190'],
            'Keterangan' => ['nullable', 'string', 'max:500'],
            'HariAtribusi' => ['required', 'integer', 'between:1,3650'],
            'Aktif' => ['boolean'],
        ];
    }
}
