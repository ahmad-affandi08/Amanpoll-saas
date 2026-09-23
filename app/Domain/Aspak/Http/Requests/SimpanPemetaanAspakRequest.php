<?php

declare(strict_types=1);

namespace App\Domain\Aspak\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class SimpanPemetaanAspakRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'AlkesAspakId' => ['required', 'string', 'exists:AlkesAspak,Id'],
            'KategoriAsetId' => ['nullable', 'string', 'exists:KategoriAset,Id'],
            'ModelAsetId' => ['nullable', 'string', 'exists:ModelAset,Id'],
        ];
    }

    /** Tepat satu sisi yang dipetakan; dua-duanya membuat pemetaannya ambigu. */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            $kategori = $this->input('KategoriAsetId');
            $model = $this->input('ModelAsetId');

            if (blank($kategori) === blank($model)) {
                $v->errors()->add('KategoriAsetId', 'Isi salah satu saja: kategori aset atau model aset.');
            }
        });
    }
}
