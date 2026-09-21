<?php

declare(strict_types=1);

namespace App\Domain\Kepatuhan\Http\Requests;

use App\Core\Organisasi\KonteksOrganisasi;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class TugaskanStandarRequest extends FormRequest
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
            'StandarKepatuhanId' => ['required', 'string', Rule::exists('StandarKepatuhan', 'Id')->where('OrganisasiId', $organisasiId)],
        ];
    }
}
