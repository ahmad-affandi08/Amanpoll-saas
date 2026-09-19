<?php

declare(strict_types=1);

namespace App\Domain\Aset\Http\Requests;

use App\Core\Organisasi\KonteksOrganisasi;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SimpanRiwayatLokasiAsetRequest extends FormRequest
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
        $organisasiId = app(KonteksOrganisasi::class)->id();

        return [
            'LokasiTujuanId' => ['nullable', 'string',
                Rule::exists('Lokasi', 'Id')->where(fn ($q) => $q->where('OrganisasiId', $organisasiId)->whereNull('DihapusPada'))],
            'Alasan' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
