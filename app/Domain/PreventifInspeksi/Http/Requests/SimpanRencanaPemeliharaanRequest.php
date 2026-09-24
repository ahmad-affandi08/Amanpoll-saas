<?php

declare(strict_types=1);

namespace App\Domain\PreventifInspeksi\Http\Requests;

use App\Domain\Platform\Http\Requests\UnitPengelolaSah;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\RencanaPemeliharaan;
use Illuminate\Foundation\Http\FormRequest;

final class SimpanRencanaPemeliharaanRequest extends FormRequest
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
        $rencana = $this->route('rencanaPemeliharaan');

        return [
            'Kode' => ['nullable', 'string', 'max:80'],
            'Nama' => ['required', 'string', 'max:200'],
            'Jenis' => ['nullable', 'string', 'max:50'],
            'TemplatDaftarPeriksaId' => ['nullable', 'string', 'size:26'],
            'Prioritas' => ['nullable', 'string', 'max:30'],
            'StrategiJadwal' => ['nullable', 'string', 'in:Interval,PenggunaanMeter,Kombinasi'],
            'IntervalNilai' => ['required', 'integer', 'min:1'],
            'IntervalSatuan' => ['required', 'string', 'in:Hari,Minggu,Bulan,Tahun'],
            'BerdasarkanMeter' => ['nullable', 'boolean'],
            'AmbangMeter' => ['nullable', 'numeric'],
            'ToleransiHari' => ['nullable', 'integer', 'min:0'],
            'BuatPerintahKerjaHariSebelum' => ['nullable', 'integer', 'min:0'],
            'Aktif' => ['nullable', 'boolean'],
            'UnitPengelolaId' => UnitPengelolaSah::aturan($rencana instanceof RencanaPemeliharaan ? $rencana->UnitPengelolaId : null),
        ];
    }
}
