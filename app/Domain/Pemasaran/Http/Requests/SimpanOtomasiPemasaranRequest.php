<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Http\Requests;

use App\Domain\Pemasaran\Domain\KatalogPemicuOtomasi;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\OtomasiPemasaran;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SimpanOtomasiPemasaranRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('platform') !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $otomasi = $this->route('otomasi');
        $id = $otomasi instanceof OtomasiPemasaran ? $otomasi->getKey() : null;

        return [
            'Kode' => [
                'nullable',
                'string',
                'max:80',
                'regex:/^[a-z0-9-]+$/',
                Rule::unique('OtomasiPemasaran', 'Kode')->ignore($id, 'Id'),
            ],
            'Nama' => ['required', 'string', 'max:190'],
            'Keterangan' => ['nullable', 'string', 'max:500'],
            // Daftar tertutup: pemicu yang tidak ada sumbernya tidak akan pernah menyala.
            'Pemicu' => ['required', Rule::in(KatalogPemicuOtomasi::kode())],
        ];
    }
}
