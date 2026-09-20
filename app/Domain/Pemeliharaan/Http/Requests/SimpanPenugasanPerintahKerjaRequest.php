<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Http\Requests;

use App\Core\Organisasi\KonteksOrganisasi;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SimpanPenugasanPerintahKerjaRequest extends FormRequest
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
            'PenggunaIds' => ['required', 'array', 'min:1'],
            'PenggunaIds.*' => ['string', 'distinct', Rule::exists('Pengguna', 'Id')->where(fn ($query) => $query->where('OrganisasiId', $organisasiId)->where('Status', 'Aktif')->whereNull('DihapusPada'))],
            'PeranTugas' => ['required', 'string', 'max:60'],
            'GantiPenugasanAktif' => ['sometimes', 'boolean'],
        ];
    }
}
