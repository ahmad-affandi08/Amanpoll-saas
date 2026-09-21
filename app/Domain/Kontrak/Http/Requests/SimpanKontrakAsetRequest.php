<?php

declare(strict_types=1);

namespace App\Domain\Kontrak\Http\Requests;

use App\Core\Organisasi\KonteksOrganisasi;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SimpanKontrakAsetRequest extends FormRequest
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
        $organisasiId = app(KonteksOrganisasi::class)->wajibId();

        return [
            'AsetId' => ['required', 'string', Rule::exists('Aset', 'Id')->where('OrganisasiId', $organisasiId)],
            'MulaiPada' => ['nullable', 'date'],
            'BerakhirPada' => ['nullable', 'date', 'after_or_equal:MulaiPada'],
            'Catatan' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
