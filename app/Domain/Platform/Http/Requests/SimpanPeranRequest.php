<?php

declare(strict_types=1);

namespace App\Domain\Platform\Http\Requests;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SimpanPeranRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $organisasiId = app(KonteksOrganisasi::class)->id();
        /** @var Peran|null $peran */
        $peran = $this->route('peran');
        $peranId = $peran?->Id;

        return [
            'Kode' => [
                'nullable', 'string', 'max:80', 'regex:/^[A-Za-z0-9_.-]+$/',
                Rule::unique('Peran', 'Kode')
                    ->where(fn ($query) => $query->where('OrganisasiId', $organisasiId))
                    ->whereNull('DihapusPada')
                    ->ignore($peranId, 'Id'),
            ],
            'Nama' => ['required', 'string', 'max:120'],
            'Keterangan' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
