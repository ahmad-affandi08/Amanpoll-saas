<?php

declare(strict_types=1);

namespace App\Domain\Kepatuhan\Http\Requests;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Kepatuhan\Infrastructure\Persistence\Models\StandarKepatuhan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SimpanStandarKepatuhanRequest extends FormRequest
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
        $organisasiId = app(KonteksOrganisasi::class)->wajibId();
        $standar = $this->route('standarKepatuhan');

        return [
            'Kode' => [
                'required', 'string', 'max:80',
                Rule::unique('StandarKepatuhan', 'Kode')
                    ->where('OrganisasiId', $organisasiId)
                    ->ignore($standar instanceof StandarKepatuhan ? $standar->Id : null, 'Id'),
            ],
            'Nama' => ['required', 'string', 'max:220'],
            'Penerbit' => ['nullable', 'string', 'max:180'],
            'VersiStandar' => ['nullable', 'string', 'max:80'],
            'JenisIndustri' => ['nullable', 'string', 'max:120'],
            'Deskripsi' => ['nullable', 'string', 'max:5000'],
            'Aktif' => ['required', 'boolean'],
        ];
    }
}
