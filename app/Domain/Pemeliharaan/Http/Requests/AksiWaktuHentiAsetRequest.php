<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Http\Requests;

use App\Core\Organisasi\KonteksOrganisasi;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class AksiWaktuHentiAsetRequest extends FormRequest
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
            'Aksi' => ['required', Rule::in(['Mulai', 'Selesai'])],
            'AsetId' => ['required', 'string', Rule::exists('Aset', 'Id')->where(fn ($query) => $query->where('OrganisasiId', $organisasiId)->whereNull('DihapusPada'))],
            'Jenis' => ['required', Rule::in(['Terencana', 'TidakTerencana'])],
            'Alasan' => ['required', 'string', 'max:2000'],
        ];
    }
}
