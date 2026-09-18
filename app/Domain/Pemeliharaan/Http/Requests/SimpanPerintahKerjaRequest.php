<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanPerintahKerjaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        // Perketat rule sesuai invariant use-case sebelum endpoint diaktifkan.
        return [
            'OrganisasiId' => ['sometimes'],
            'Nomor' => ['sometimes'],
            'KeluhanId' => ['nullable'],
            'TingkatLayananId' => ['nullable'],
            'Jenis' => ['sometimes'],
            'Judul' => ['sometimes'],
            'Deskripsi' => ['nullable'],
            'Prioritas' => ['sometimes'],
            'Status' => ['sometimes'],
            'LokasiId' => ['nullable'],
            'UnitOrganisasiId' => ['nullable'],
            'DijadwalkanMulaiPada' => ['nullable'],
            'DijadwalkanSelesaiPada' => ['nullable'],
            'DiterimaPada' => ['nullable'],
            'DimulaiPada' => ['nullable'],
            'DiselesaikanPada' => ['nullable'],
            'DitutupPada' => ['nullable'],
            'BatasResponsPada' => ['nullable'],
            'BatasPenyelesaianPada' => ['nullable'],
            'PersentaseSelesai' => ['sometimes'],
            'MembutuhkanWaktuHenti' => ['sometimes'],
            'MembutuhkanPersetujuan' => ['sometimes'],
            'RingkasanPenyelesaian' => ['nullable'],
            'DibuatOleh' => ['nullable'],
            'Versi' => ['sometimes'],
        ];
    }
}
