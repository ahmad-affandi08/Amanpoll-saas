<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Http\Requests;

use App\Core\Organisasi\KonteksOrganisasi;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SimpanAnggaranRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $wajib = $this->isMethod('post') ? 'required' : 'sometimes';
        $organisasiId = app(KonteksOrganisasi::class)->wajibId();

        return [
            'UnitOrganisasiId' => ['nullable', 'string', Rule::exists('UnitOrganisasi', 'Id')->where('OrganisasiId', $organisasiId)],
            'Kode' => [$wajib, 'string', 'max:80'],
            'Nama' => [$wajib, 'string', 'max:180'],
            'Tahun' => [$wajib, 'integer', 'between:2000,2100'],
            'MataUang' => ['sometimes', 'string', 'size:3'],
            'Jumlah' => [$wajib, 'numeric', 'decimal:0,2', 'min:0.01', 'max:99999999999999.99'],
        ];
    }
}
