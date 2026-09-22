<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Http\Requests;

use App\Domain\Pemasaran\Domain\Enums\JenisKomisiPartner;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Validator;

final class SimpanAturanKomisiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('platform') !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'ProgramPartnerId' => ['required', 'string', 'exists:ProgramPartner,Id'],
            'PartnerId' => ['nullable', 'string', 'exists:Partner,Id'],
            'Nama' => ['required', 'string', 'max:190'],
            'Jenis' => ['required', new Enum(JenisKomisiPartner::class)],
            'Nilai' => ['required', 'numeric', 'gt:0', 'max:100000000'],
            'MaksPembayaran' => ['nullable', 'integer', 'between:1,1000'],
            'Aktif' => ['boolean'],
            'BerlakuDari' => ['nullable', 'date'],
            'BerlakuSampai' => ['nullable', 'date', 'after_or_equal:BerlakuDari'],
        ];
    }

    /**
     * Pesan ramah di fieldnya; penjaga sebenarnya tetap `LayananProgramPartner`.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->input('Jenis') !== JenisKomisiPartner::Persentase->value) {
                    return;
                }

                if ((float) $this->input('Nilai') > 100) {
                    $validator->errors()->add('Nilai', 'Komisi persentase tidak boleh melampaui 100%.');
                }
            },
        ];
    }
}
