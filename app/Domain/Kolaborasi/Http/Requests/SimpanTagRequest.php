<?php

declare(strict_types=1);

namespace App\Domain\Kolaborasi\Http\Requests;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\Tag;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SimpanTagRequest extends FormRequest
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
        /** @var Tag|null $tag */
        $tag = $this->route('tag');

        return [
            'Nama' => [
                'required', 'string', 'max:100',
                Rule::unique('Tag', 'Nama')->where(fn ($q) => $q->where('OrganisasiId', $organisasiId))->ignore($tag?->Id, 'Id'),
            ],
            'Warna' => ['nullable', 'string', 'max:20'],
        ];
    }
}
