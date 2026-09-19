<?php

declare(strict_types=1);

namespace App\Domain\Kolaborasi\Http\Requests;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\DefinisiKolomKustom;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SimpanDefinisiKolomKustomRequest extends FormRequest
{
    public const TIPE_DATA_DIIZINKAN = ['Teks', 'Angka', 'Tanggal', 'Boolean', 'Pilihan', 'PilihanGanda'];

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $organisasiId = app(KonteksOrganisasi::class)->id();
        /** @var DefinisiKolomKustom|null $definisiKolomKustom */
        $definisiKolomKustom = $this->route('definisiKolomKustom');
        $jenisEntitas = $this->input('JenisEntitas');

        return [
            'JenisEntitas' => ['required', 'string'],
            'Kode' => [
                'required', 'string', 'max:80', 'regex:/^[A-Za-z][A-Za-z0-9_]*$/',
                Rule::unique('DefinisiKolomKustom', 'Kode')
                    ->where(fn ($q) => $q->where('OrganisasiId', $organisasiId)->where('JenisEntitas', $jenisEntitas))
                    ->ignore($definisiKolomKustom?->Id, 'Id'),
            ],
            'Label' => ['required', 'string', 'max:160'],
            'TipeData' => ['required', Rule::in(self::TIPE_DATA_DIIZINKAN)],
            'Wajib' => ['sometimes', 'boolean'],
            'Pilihan' => ['required_if:TipeData,Pilihan,PilihanGanda', 'nullable', 'array', 'min:1'],
            'Pilihan.*' => ['string'],
            'AturanValidasi' => ['nullable', 'array'],
            'AturanValidasi.*' => ['string'],
            'NilaiBawaan' => ['nullable'],
            'Urutan' => ['sometimes', 'integer'],
            'Aktif' => ['sometimes', 'boolean'],
        ];
    }
}
