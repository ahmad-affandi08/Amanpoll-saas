<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Http\Requests;

use App\Domain\Pemasaran\Domain\Enums\JenisRewardReferral;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\ProgramReferral;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

final class SimpanProgramReferralRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('platform') !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $program = $this->route('program');
        $id = $program instanceof ProgramReferral ? $program->getKey() : null;

        return [
            'Kode' => [
                'nullable',
                'string',
                'max:80',
                'regex:/^[a-z0-9-]+$/',
                Rule::unique('ProgramReferral', 'Kode')->ignore($id, 'Id'),
            ],
            'Nama' => ['required', 'string', 'max:190'],
            'Keterangan' => ['nullable', 'string', 'max:500'],
            'JenisReward' => ['required', new Enum(JenisRewardReferral::class)],
            'NilaiReward' => ['required', 'numeric', 'between:0,100000000'],
            'HariKedaluwarsa' => ['required', 'integer', 'between:1,3650'],
            'Aktif' => ['boolean'],
        ];
    }
}
