<?php

declare(strict_types=1);

namespace App\Domain\Kontrak\Http\Requests;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Kontrak\Infrastructure\Persistence\Models\Kontrak;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SimpanKontrakRequest extends FormRequest
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
        $organisasiId = app(KonteksOrganisasi::class)->wajibId();
        $kontrak = $this->route('kontrak');

        return [
            'PenyediaId' => ['nullable', 'string', Rule::exists('Penyedia', 'Id')->where('OrganisasiId', $organisasiId)],
            'Nomor' => [
                'required', 'string', 'max:120',
                Rule::unique('Kontrak', 'Nomor')
                    ->where('OrganisasiId', $organisasiId)
                    ->ignore($kontrak instanceof Kontrak ? $kontrak->Id : null, 'Id'),
            ],
            'Nama' => ['required', 'string', 'max:220'],
            'Jenis' => ['required', Rule::in(['Pemeliharaan', 'Layanan', 'Sewa', 'Pembelian', 'Lainnya'])],
            'MulaiPada' => ['required', 'date'],
            'BerakhirPada' => ['required', 'date', 'after_or_equal:MulaiPada'],
            'Nilai' => ['nullable', 'numeric', 'decimal:0,2', 'min:0'],
            'MataUang' => ['required', 'string', 'size:3'],
            'TingkatLayananId' => ['nullable', 'string', Rule::exists('TingkatLayanan', 'Id')->where('OrganisasiId', $organisasiId)],
            'PeringatanHariSebelum' => ['required', 'integer', 'min:0', 'max:365'],
            'Catatan' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
