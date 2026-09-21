<?php

declare(strict_types=1);

namespace App\Domain\Notifikasi\Http\Requests;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Notifikasi\Domain\Enums\KanalNotifikasi;
use App\Domain\Notifikasi\Infrastructure\Persistence\Models\TemplatNotifikasi;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SimpanTemplatNotifikasiRequest extends FormRequest
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
        /** @var TemplatNotifikasi|null $templatNotifikasi */
        $templatNotifikasi = $this->route('templatNotifikasi');

        return [
            'Kode' => [
                'required', 'string', 'max:100',
                Rule::unique('TemplatNotifikasi', 'Kode')
                    ->where(fn ($q) => $q->where('OrganisasiId', $organisasiId)->where('Kanal', $this->input('Kanal')))
                    ->ignore($templatNotifikasi?->Id, 'Id'),
            ],
            'Kanal' => ['required', Rule::enum(KanalNotifikasi::class)],
            'JudulTemplat' => ['nullable', 'string', 'max:255'],
            'IsiTemplat' => ['required', 'string'],
            'Variabel' => ['nullable', 'array'],
            'Aktif' => ['sometimes', 'boolean'],
        ];
    }
}
