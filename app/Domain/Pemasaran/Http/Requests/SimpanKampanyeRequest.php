<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Http\Requests;

use App\Domain\Pemasaran\Domain\Enums\ChannelKampanye;
use App\Domain\Pemasaran\Domain\Enums\ObjectiveKampanye;
use App\Domain\Pemasaran\Domain\Enums\StatusKampanye;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Kampanye;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SimpanKampanyeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('platform') !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $kampanye = $this->route('kampanye');
        $id = $kampanye instanceof Kampanye ? $kampanye->getKey() : null;

        return [
            // Kode inilah yang dipakai sebagai utm_campaign, jadi keunikannya
            // menentukan apakah satu kampanye terhitung satu atau terpecah.
            'Kode' => ['required', 'string', 'max:100', Rule::unique('Kampanye', 'Kode')->ignore($id, 'Id')],
            'Nama' => ['required', 'string', 'max:180'],
            'Objective' => ['required', Rule::enum(ObjectiveKampanye::class)],
            'Status' => ['required', Rule::enum(StatusKampanye::class)],
            'MulaiPada' => ['nullable', 'date'],
            'SelesaiPada' => ['nullable', 'date', 'after_or_equal:MulaiPada'],
            'Channel' => ['nullable', 'array'],
            'Channel.*' => [Rule::enum(ChannelKampanye::class), 'distinct'],
            'Catatan' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
