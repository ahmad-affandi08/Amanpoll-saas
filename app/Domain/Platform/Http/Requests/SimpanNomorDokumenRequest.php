<?php

declare(strict_types=1);

namespace App\Domain\Platform\Http\Requests;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\NomorDokumen;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SimpanNomorDokumenRequest extends FormRequest
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
        /** @var NomorDokumen|null $nomorDokumen */
        $nomorDokumen = $this->route('nomorDokumen');
        $nomorDokumenId = $nomorDokumen?->Id;

        return [
            'JenisDokumen' => ['required', 'string', 'max:80',
                Rule::unique('NomorDokumen', 'JenisDokumen')->where(fn ($q) => $q->where('OrganisasiId', $organisasiId))->ignore($nomorDokumenId, 'Id')],
            'Awalan' => ['nullable', 'string', 'max:40'],
            'FormatNomor' => ['required', 'string', 'max:160', 'regex:/\{Nomor(:\d+)?\}/'],
            'ResetPeriode' => ['required', 'string', Rule::in(['Tahunan', 'Bulanan', 'TidakAda'])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'FormatNomor.regex' => 'Format nomor wajib mengandung placeholder {Nomor} atau {Nomor:lebar}.',
        ];
    }
}
