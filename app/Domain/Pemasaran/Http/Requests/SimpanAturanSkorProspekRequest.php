<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Http\Requests;

use App\Domain\Pemasaran\Domain\KatalogPeristiwaSkor;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\AturanSkorProspek;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SimpanAturanSkorProspekRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('platform') !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $aturan = $this->route('aturan');
        $id = $aturan instanceof AturanSkorProspek ? $aturan->getKey() : null;

        return [
            // Daftar tertutup. Bobot untuk sinyal yang tidak dikenal tidak akan
            // pernah terpakai, dan menyimpannya hanya menunda kebingungannya.
            'Peristiwa' => [
                'required',
                Rule::in(KatalogPeristiwaSkor::kode()),
                Rule::unique('AturanSkorProspek', 'Peristiwa')->ignore($id, 'Id'),
            ],
            'Bobot' => ['required', 'integer', 'between:-1000,1000'],
            'Aktif' => ['boolean'],
            'Keterangan' => ['nullable', 'string', 'max:500'],
        ];
    }
}
