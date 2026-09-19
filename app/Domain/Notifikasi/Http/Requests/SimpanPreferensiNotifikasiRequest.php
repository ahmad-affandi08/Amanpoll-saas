<?php

declare(strict_types=1);

namespace App\Domain\Notifikasi\Http\Requests;

use App\Domain\Notifikasi\Domain\KatalogPeristiwaNotifikasi;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SimpanPreferensiNotifikasiRequest extends FormRequest
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
        return [
            'JenisPeristiwa' => ['required', Rule::in(KatalogPeristiwaNotifikasi::kodeDikenal())],
            'Kanal' => ['required', Rule::in(SimpanTemplatNotifikasiRequest::KANAL_DIIZINKAN)],
            'Aktif' => ['required', 'boolean'],
        ];
    }
}
