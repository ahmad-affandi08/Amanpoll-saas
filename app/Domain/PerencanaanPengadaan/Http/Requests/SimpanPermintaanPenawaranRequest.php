<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Http\Requests;

use App\Core\Organisasi\KonteksOrganisasi;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SimpanPermintaanPenawaranRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $organisasiId = app(KonteksOrganisasi::class)->wajibId();

        return [
            'PermintaanPembelianId' => ['required', 'string', Rule::exists('PermintaanPembelian', 'Id')->where('OrganisasiId', $organisasiId)],
            'BatasPenawaran' => ['required', 'date', 'after:now'],
            'Catatan' => ['nullable', 'string', 'max:3000'],
            'PenyediaIds' => ['required', 'array', 'min:1'],
            'PenyediaIds.*' => ['required', 'string', 'distinct', Rule::exists('Penyedia', 'Id')->where('OrganisasiId', $organisasiId)],
        ];
    }
}
