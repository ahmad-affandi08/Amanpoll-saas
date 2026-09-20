<?php

declare(strict_types=1);

namespace App\Domain\Kalibrasi\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SimpanPelaksanaanKalibrasiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'AsetId' => ['required', 'string', 'max:26'],
            'Nomor' => ['nullable', 'string', 'max:100'],
            'RencanaKalibrasiId' => ['nullable', 'string', 'max:26'],
            'JenisKalibrasiId' => ['nullable', 'string', 'max:26'],
            'PenyediaId' => ['nullable', 'string', 'max:26'],
            'PerintahKerjaId' => ['nullable', 'string', 'max:26'],
            'TanggalKalibrasi' => ['required', 'date'],
            'TanggalBerlakuSampai' => ['nullable', 'date'],
            'Hasil' => ['nullable', 'string', 'max:40'],
            'NomorSertifikat' => ['nullable', 'string', 'max:180'],
            'Laboratorium' => ['nullable', 'string', 'max:200'],
            'KondisiLingkungan' => ['nullable', 'array'],
            'Catatan' => ['nullable', 'string'],
            'DilaksanakanOleh' => ['nullable', 'string', 'max:26'],
        ];
    }
}
