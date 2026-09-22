<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Http\Requests;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Persediaan\Domain\Enums\StatusSukuCadang;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\SukuCadang;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SimpanSukuCadangRequest extends FormRequest
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
        /** @var SukuCadang|null $sukuCadang */
        $sukuCadang = $this->route('sukuCadang');

        return [
            'Kode' => ['nullable', 'string', 'max:80',
                Rule::unique('SukuCadang', 'Kode')->where(fn ($q) => $q->where('OrganisasiId', $organisasiId))->whereNull('DihapusPada')->ignore($sukuCadang?->Id, 'Id')],
            'Nama' => ['required', 'string', 'max:200'],
            'KategoriSukuCadangId' => ['nullable', 'string',
                Rule::exists('KategoriSukuCadang', 'Id')->where(fn ($q) => $q->where('OrganisasiId', $organisasiId))],
            'NomorBagian' => ['nullable', 'string', 'max:160'],
            'KodeBatang' => ['nullable', 'string', 'max:255'],
            'SatuanDasar' => ['required', 'string', 'max:50'],
            'StokMinimum' => ['required', 'numeric', 'min:0'],
            'StokMaksimum' => ['nullable', 'numeric', 'min:0'],
            'TitikPesanUlang' => ['nullable', 'numeric', 'min:0'],
            'HargaRataRata' => ['nullable', 'numeric', 'min:0'],
            'MemakaiBatch' => ['boolean'],
            'MemakaiKadaluarsa' => ['boolean'],
            'Status' => ['required', 'string', Rule::enum(StatusSukuCadang::class)],
        ];
    }
}
