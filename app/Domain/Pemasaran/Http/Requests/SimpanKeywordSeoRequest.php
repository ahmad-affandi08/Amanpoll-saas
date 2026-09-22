<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Http\Requests;

use App\Domain\Pemasaran\Domain\Enums\IntentKeyword;
use App\Domain\Pemasaran\Domain\Enums\PrioritasKeyword;
use App\Domain\Pemasaran\Domain\Enums\StatusKeywordSeo;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\KeywordSeo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SimpanKeywordSeoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('platform') !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $keyword = $this->route('keyword');
        $id = $keyword instanceof KeywordSeo ? $keyword->getKey() : null;

        return [
            'Keyword' => [
                'required',
                'string',
                'max:190',
                Rule::unique('KeywordSeo', 'Keyword')->ignore($id, 'Id'),
            ],
            'ClusterSeoId' => ['nullable', 'string', 'exists:ClusterSeo,Id'],
            'Intent' => ['required', Rule::enum(IntentKeyword::class)],
            'TargetUrl' => ['nullable', 'string', 'max:500'],
            'Prioritas' => ['required', Rule::enum(PrioritasKeyword::class)],
            'Status' => ['required', Rule::enum(StatusKeywordSeo::class)],
            'Catatan' => ['nullable', 'string', 'max:500'],
        ];
    }
}
