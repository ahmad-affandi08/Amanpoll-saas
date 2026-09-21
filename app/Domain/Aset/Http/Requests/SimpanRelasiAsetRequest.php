<?php

declare(strict_types=1);

namespace App\Domain\Aset\Http\Requests;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Aset\Domain\Enums\JenisRelasiAset;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SimpanRelasiAsetRequest extends FormRequest
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
            'AsetAnakId' => ['required', 'string',
                Rule::exists('Aset', 'Id')->where(fn ($q) => $q->where('OrganisasiId', $organisasiId)->whereNull('DihapusPada'))],
            'JenisRelasi' => ['required', 'string', Rule::enum(JenisRelasiAset::class)],
            'Jumlah' => ['nullable', 'numeric', 'min:0'],
            'MulaiPada' => ['nullable', 'date'],
            'SelesaiPada' => ['nullable', 'date', 'after_or_equal:MulaiPada'],
        ];
    }
}
