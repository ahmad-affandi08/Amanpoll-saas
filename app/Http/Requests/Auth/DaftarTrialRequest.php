<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

final class DaftarTrialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'NamaOrganisasi' => ['required', 'string', 'max:190'],
            'Nama' => ['required', 'string', 'max:190'],
            // Keunikan email berlaku per organisasi, dan organisasinya belum ada saat ini.
            'Email' => ['required', 'email:rfc', 'max:190'],
            'Telepon' => ['nullable', 'string', 'max:60'],
            'KataSandi' => ['required', 'confirmed', Password::min(8)],
            'TokenKartu' => ['nullable', 'string', 'max:255'],
            'Persetujuan' => ['accepted'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['Persetujuan.accepted' => 'Persetujuan syarat layanan diperlukan untuk memulai trial.'];
    }
}
