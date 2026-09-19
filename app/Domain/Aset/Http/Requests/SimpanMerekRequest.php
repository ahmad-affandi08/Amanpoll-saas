<?php

declare(strict_types=1);

namespace App\Domain\Aset\Http\Requests;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Aset\Infrastructure\Persistence\Models\Merek;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SimpanMerekRequest extends FormRequest
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
        /** @var Merek|null $merek */
        $merek = $this->route('merek');
        $merekId = $merek?->Id;

        return [
            'Nama' => ['required', 'string', 'max:160',
                Rule::unique('Merek', 'Nama')->where(fn ($q) => $q->where('OrganisasiId', $organisasiId))->ignore($merekId, 'Id')],
            'NegaraAsal' => ['nullable', 'string', 'max:100'],
            'Website' => ['nullable', 'url', 'max:255'],
        ];
    }
}
