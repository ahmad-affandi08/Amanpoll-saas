<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Http\Requests;

use App\Core\Organisasi\KonteksOrganisasi;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SimpanAnalisisKegagalanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $organisasiId = app(KonteksOrganisasi::class)->wajibId();
        $kode = fn (string $jenis): array => ['nullable', 'string', Rule::exists('KodeKegagalan', 'Id')->where(fn ($query) => $query->where('OrganisasiId', $organisasiId)->where('Jenis', $jenis)->where('Aktif', true))];

        return [
            'KodeMasalahId' => $kode('Masalah'),
            'KodePenyebabId' => $kode('Penyebab'),
            'KodeTindakanId' => $kode('Tindakan'),
            'AkarMasalah' => ['required', 'string', 'max:10000'],
            'TindakanKorektif' => ['required', 'string', 'max:10000'],
            'TindakanPencegahan' => ['nullable', 'string', 'max:10000'],
        ];
    }
}
