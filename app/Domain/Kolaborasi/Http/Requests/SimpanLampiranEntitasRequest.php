<?php

declare(strict_types=1);

namespace App\Domain\Kolaborasi\Http\Requests;

use App\Domain\Aset\Application\Services\GaleriFotoAset;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SimpanLampiranEntitasRequest extends FormRequest
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
        return [
            'JenisEntitas' => ['required', 'string'],
            'EntitasId' => ['required', 'string'],
            'BerkasId' => ['required', 'string'],
            // Kategori foto galeri aset dikelola domain Aset (batas 10, foto utama).
            'Kategori' => ['nullable', 'string', 'max:80', Rule::notIn([GaleriFotoAset::KATEGORI])],
            'Keterangan' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
