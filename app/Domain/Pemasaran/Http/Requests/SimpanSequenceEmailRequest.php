<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Http\Requests;

use App\Domain\Pemasaran\Infrastructure\Persistence\Models\SequenceEmailPemasaran;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SimpanSequenceEmailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('platform') !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $sequence = $this->route('sequence');
        $id = $sequence instanceof SequenceEmailPemasaran ? $sequence->getKey() : null;

        return [
            'Kode' => [
                'nullable',
                'string',
                'max:80',
                'regex:/^[a-z0-9-]+$/',
                Rule::unique('SequenceEmailPemasaran', 'Kode')->ignore($id, 'Id'),
            ],
            'Nama' => ['required', 'string', 'max:190'],
            'Keterangan' => ['nullable', 'string', 'max:500'],
            'Aktif' => ['boolean'],
        ];
    }
}
