<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Http\Requests;

use App\Domain\Pemasaran\Domain\Enums\JenisPartner;
use App\Domain\Pemasaran\Domain\Enums\StatusPartner;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Partner;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

final class SimpanPartnerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('platform') !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $partner = $this->route('partner');
        $id = $partner instanceof Partner ? $partner->getKey() : null;

        return [
            'ProgramPartnerId' => ['required', 'string', 'exists:ProgramPartner,Id'],
            'NamaPerusahaan' => ['required', 'string', 'max:190'],
            // Daftar tertutup bagian 21; jenis di luar daftar tidak pernah tersimpan.
            'Jenis' => ['required', new Enum(JenisPartner::class)],
            'NamaPic' => ['required', 'string', 'max:190'],
            'EmailPic' => [
                'required',
                'email',
                'max:190',
                Rule::unique('Partner', 'EmailPic')->ignore($id, 'Id'),
            ],
            'TeleponPic' => ['nullable', 'string', 'max:40'],
            'Status' => ['required', new Enum(StatusPartner::class)],
            'ReferensiPerjanjian' => ['nullable', 'string', 'max:190'],
            'ReferensiPayout' => ['nullable', 'string', 'max:190'],
            // Wajib saat membuat, opsional saat menyunting: menyunting data tidak boleh mengunci pemiliknya keluar.
            'KataSandi' => [$id === null ? 'required' : 'nullable', 'string', 'min:12', 'max:255'],
        ];
    }
}
