<?php

declare(strict_types=1);

namespace App\Domain\Platform\Http\Requests;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

final class SimpanPenggunaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $organisasiId = app(KonteksOrganisasi::class)->id();
        /** @var Pengguna|null $pengguna */
        $pengguna = $this->route('pengguna');
        $penggunaId = $pengguna?->Id;

        return [
            'UnitOrganisasiId' => [
                'nullable', 'string',
                Rule::exists('UnitOrganisasi', 'Id')->where(fn ($query) => $query->where('OrganisasiId', $organisasiId)),
            ],
            'Nama' => ['required', 'string', 'max:180'],
            'Email' => [
                'required', 'email', 'max:180',
                Rule::unique('Pengguna', 'Email')
                    ->where(fn ($query) => $query->where('OrganisasiId', $organisasiId))
                    ->whereNull('DihapusPada')
                    ->ignore($penggunaId, 'Id'),
            ],
            'Telepon' => ['nullable', 'string', 'max:50'],
            'KataSandi' => [$penggunaId ? 'nullable' : 'required', Password::min(8)],
            'NomorPegawai' => ['nullable', 'string', 'max:80'],
            'Jabatan' => ['nullable', 'string', 'max:120'],
            'JenisPengguna' => ['required', 'string', Rule::in(['Internal', 'Eksternal'])],
        ];
    }
}
