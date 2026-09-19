<?php

declare(strict_types=1);

namespace App\Domain\Penyedia\Http\Requests;

use App\Core\Organisasi\KonteksOrganisasi;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SimpanPenyediaKategoriRequest extends FormRequest
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
            'KategoriPenyediaId' => ['required', 'string',
                Rule::exists('KategoriPenyedia', 'Id')->where(fn ($q) => $q->where('OrganisasiId', $organisasiId))],
        ];
    }
}
