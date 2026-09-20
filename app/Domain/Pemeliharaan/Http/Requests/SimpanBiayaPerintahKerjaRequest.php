<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Http\Requests;

use App\Core\Organisasi\KonteksOrganisasi;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SimpanBiayaPerintahKerjaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $organisasiId = app(KonteksOrganisasi::class)->wajibId();

        return [
            'JenisBiaya' => ['required', Rule::in(['TenagaKerja', 'Sparepart', 'Vendor', 'Lainnya'])],
            'Deskripsi' => ['nullable', 'string', 'max:255'],
            'Jumlah' => ['required', 'numeric', 'min:0'],
            'MataUang' => ['required', 'string', 'size:3'],
            'PenyediaId' => ['nullable', 'string', Rule::exists('Penyedia', 'Id')->where(fn ($query) => $query->where('OrganisasiId', $organisasiId)->whereNull('DihapusPada'))],
            'TanggalBiaya' => ['required', 'date'],
        ];
    }
}
