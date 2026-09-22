<?php

declare(strict_types=1);

namespace App\Domain\Langganan\Http\Requests;

use App\Domain\Langganan\Domain\KatalogFitur;
use App\Domain\Langganan\Infrastructure\Persistence\Models\PaketLangganan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SimpanPaketPlatformRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('platform') !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        // Atribut Eloquent bukan properti kelas, jadi kuncinya dibaca lewat getKey().
        $paket = $this->route('paketLangganan');
        $abaikan = $paket instanceof PaketLangganan ? $paket->getKey() : null;

        return [
            'Kode' => [
                'required', 'string', 'max:80', 'regex:/^[A-Za-z0-9._-]+$/',
                Rule::unique('PaketLangganan', 'Kode')->ignore($abaikan, 'Id'),
            ],
            'Nama' => ['required', 'string', 'max:160'],
            'Deskripsi' => ['nullable', 'string', 'max:2000'],
            'HargaBulanan' => ['required', 'numeric', 'min:0'],
            'HargaTahunan' => ['required', 'numeric', 'min:0'],
            'MataUang' => ['nullable', 'string', 'size:3'],
            'Aktif' => ['required', 'boolean'],
            'Fitur' => ['required', 'array', 'min:1'],
            'Fitur.*.Kode' => ['required', 'string', Rule::in(KatalogFitur::kode())],
            'Fitur.*.Diizinkan' => ['required', 'boolean'],
            'Fitur.*.BatasNilai' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
