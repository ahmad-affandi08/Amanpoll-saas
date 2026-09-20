<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Http\Requests;

use App\Core\Organisasi\KonteksOrganisasi;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class GunakanSukuCadangPerintahKerjaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $organisasiId = app(KonteksOrganisasi::class)->wajibId();

        return [
            'ReservasiSukuCadangId' => ['required', 'string', Rule::exists('ReservasiSukuCadang', 'Id')->where(fn ($query) => $query->where('OrganisasiId', $organisasiId))],
            'Aksi' => ['required', Rule::in(['Pakai', 'Kembalikan'])],
        ];
    }
}
