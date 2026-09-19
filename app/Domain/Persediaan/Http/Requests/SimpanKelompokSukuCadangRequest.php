<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Http\Requests;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\KelompokSukuCadang;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SimpanKelompokSukuCadangRequest extends FormRequest
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
        /** @var KelompokSukuCadang|null $kelompokSukuCadang */
        $kelompokSukuCadang = $this->route('kelompokSukuCadang');
        $sukuCadangId = $kelompokSukuCadang !== null ? $kelompokSukuCadang->SukuCadangId : $this->input('SukuCadangId');

        return [
            'SukuCadangId' => ['required', 'string',
                Rule::exists('SukuCadang', 'Id')->where(fn ($q) => $q->where('OrganisasiId', $organisasiId)->whereNull('DihapusPada'))],
            'NomorBatch' => ['required', 'string', 'max:120',
                Rule::unique('KelompokSukuCadang', 'NomorBatch')->where(fn ($q) => $q->where('SukuCadangId', $sukuCadangId))->ignore($kelompokSukuCadang?->Id, 'Id')],
            'TanggalProduksi' => ['nullable', 'date'],
            'TanggalKadaluarsa' => ['nullable', 'date', 'after_or_equal:TanggalProduksi'],
            'HargaPerolehan' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
