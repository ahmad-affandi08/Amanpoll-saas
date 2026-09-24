<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Http\Requests;

use App\Domain\Persediaan\Domain\Enums\JenisMutasiStok;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SimpanMutasiStokRequest extends FormRequest
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
            'Jenis' => ['required', 'string', Rule::enum(JenisMutasiStok::class)],
            // Kedua sisi harus terlihat: pengguna berlingkup IT tidak bisa memindahkan stok ke atau dari gudang IPSRS.
            'GudangAsalId' => ['nullable', 'string', new GudangTerlihat],
            'GudangTujuanId' => ['nullable', 'string', new GudangTerlihat],
            'ReferensiJenis' => ['nullable', 'string', 'max:80'],
            'ReferensiId' => ['nullable', 'string'],
            'Tanggal' => ['nullable', 'date'],
            'Catatan' => ['nullable', 'string'],
        ];
    }
}
