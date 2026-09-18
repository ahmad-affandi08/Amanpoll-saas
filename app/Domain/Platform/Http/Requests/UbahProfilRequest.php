<?php

declare(strict_types=1);

namespace App\Domain\Platform\Http\Requests;

use App\Core\Organisasi\KonteksOrganisasi;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UbahProfilRequest extends FormRequest
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
            'Nama' => ['required', 'string', 'max:180'],
            'Email' => [
                'required', 'email', 'max:180',
                Rule::unique('Pengguna', 'Email')
                    ->where(fn ($query) => $query->where('OrganisasiId', app(KonteksOrganisasi::class)->id()))
                    ->whereNull('DihapusPada')
                    ->ignore($this->user()->Id, 'Id'),
            ],
            'Telepon' => ['nullable', 'string', 'max:50'],
        ];
    }
}
