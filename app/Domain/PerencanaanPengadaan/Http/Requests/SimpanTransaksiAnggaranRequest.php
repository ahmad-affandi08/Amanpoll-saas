<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Http\Requests;

use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\TransaksiAnggaran;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SimpanTransaksiAnggaranRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'Jenis' => ['required', 'string', Rule::in(TransaksiAnggaran::DAFTAR_JENIS)],
            'ReferensiJenis' => ['nullable', 'string', 'max:80', 'required_with:ReferensiId'],
            'ReferensiId' => ['nullable', 'string', 'size:26', 'required_with:ReferensiJenis'],
            'Jumlah' => ['required', 'numeric', 'decimal:0,2', 'not_in:0,0.0,0.00', 'max:99999999999999.99'],
            'Tanggal' => ['required', 'date'],
            'Keterangan' => ['nullable', 'string', 'max:2000', Rule::requiredIf($this->input('Jenis') === TransaksiAnggaran::JENIS_PENYESUAIAN)],
        ];
    }
}
