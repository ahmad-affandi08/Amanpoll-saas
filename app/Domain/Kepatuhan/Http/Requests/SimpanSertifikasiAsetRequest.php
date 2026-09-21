<?php

declare(strict_types=1);

namespace App\Domain\Kepatuhan\Http\Requests;

use App\Core\Organisasi\KonteksOrganisasi;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SimpanSertifikasiAsetRequest extends FormRequest
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

        return [
            'AsetId' => ['required_without:sertifikasiAset', 'nullable', 'string', Rule::exists('Aset', 'Id')->where('OrganisasiId', $organisasiId)],
            'JenisSertifikasi' => ['required', 'string', 'max:120'],
            'NomorSertifikat' => ['nullable', 'string', 'max:180'],
            'Penerbit' => ['nullable', 'string', 'max:180'],
            'TerbitPada' => ['nullable', 'date'],
            'BerlakuSampai' => ['nullable', 'date', 'after_or_equal:TerbitPada'],
            'BerkasId' => ['nullable', 'string', Rule::exists('Berkas', 'Id')->where('OrganisasiId', $organisasiId)],
        ];
    }
}
