<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Http\Requests;

use App\Domain\Pemasaran\Infrastructure\Persistence\Models\LeadPartner;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class KirimLeadPartnerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('partner') !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'NamaPerusahaan' => ['required', 'string', 'max:190'],
            'NamaKontak' => ['required', 'string', 'max:190'],
            'Email' => ['required', 'email', 'max:190'],
            'Telepon' => ['nullable', 'string', 'max:40'],
            'Catatan' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Pesan ramah di field emailnya; penjaga sebenarnya tetap indeks unik dan `PelacakLeadPartner`.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $email = $this->input('Email');

                if (! is_string($email) || $email === '') {
                    return;
                }

                $sudahAda = LeadPartner::query()
                    ->where('Email', mb_strtolower(trim($email)))
                    ->exists();

                if ($sudahAda) {
                    $validator->errors()->add('Email', 'Lead dengan alamat email ini sudah pernah diklaim.');
                }
            },
        ];
    }
}
