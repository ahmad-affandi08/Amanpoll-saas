<?php

declare(strict_types=1);

namespace App\Domain\Aset\Http\Requests;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Aset\Domain\Enums\StatusGaransiAset;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SimpanGaransiAsetRequest extends FormRequest
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
            'PenyediaId' => ['nullable', 'string',
                Rule::exists('Penyedia', 'Id')->where(fn ($q) => $q->where('OrganisasiId', $organisasiId)->whereNull('DihapusPada'))],
            'NomorGaransi' => ['nullable', 'string', 'max:160'],
            'JenisGaransi' => ['nullable', 'string', 'max:60'],
            'MulaiPada' => ['required', 'date'],
            'BerakhirPada' => ['required', 'date', 'after_or_equal:MulaiPada'],
            'Cakupan' => ['nullable', 'string'],
            'Status' => ['required', 'string', Rule::enum(StatusGaransiAset::class)],
        ];
    }
}
