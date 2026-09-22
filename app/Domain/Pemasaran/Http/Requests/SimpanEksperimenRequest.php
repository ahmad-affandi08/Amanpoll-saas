<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Http\Requests;

use App\Domain\Pemasaran\Domain\Enums\MetrikEksperimen;
use App\Domain\Pemasaran\Domain\Enums\TargetEksperimen;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\EksperimenPemasaran;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class SimpanEksperimenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('platform') !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $eksperimen = $this->route('eksperimen');
        $id = $eksperimen instanceof EksperimenPemasaran ? $eksperimen->getKey() : null;

        return [
            'Kode' => [
                'required', 'string', 'max:80',
                Rule::unique('EksperimenPemasaran', 'Kode')->ignore($id, 'Id'),
            ],
            'Nama' => ['required', 'string', 'max:190'],
            'Target' => ['required', Rule::enum(TargetEksperimen::class)],
            'MetrikUtama' => ['required', Rule::enum(MetrikEksperimen::class)],
            'Hipotesis' => ['nullable', 'string', 'max:500'],
            'MinimumSampel' => ['required', 'integer', 'min:1', 'max:1000000'],
            'Varian' => ['present', 'array', 'min:2', 'max:8'],
            'Varian.*.Kode' => ['required', 'string', 'max:20', 'distinct'],
            'Varian.*.Nama' => ['required', 'string', 'max:190'],
            'Varian.*.Bobot' => ['required', 'integer', 'min:0', 'max:1000'],
            'Varian.*.Kontrol' => ['required', 'boolean'],
        ];
    }

    /**
     * Tepat satu varian kontrol, dan bobotnya tidak boleh nol seluruhnya.
     *
     * @return list<callable>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $varian = $this->input('Varian');

            if (! is_array($varian)) {
                return;
            }

            $kontrol = 0;
            $totalBobot = 0;

            foreach ($varian as $satu) {
                if (! is_array($satu)) {
                    continue;
                }

                $kontrol += filter_var($satu['Kontrol'] ?? false, FILTER_VALIDATE_BOOL) ? 1 : 0;
                $totalBobot += (int) ($satu['Bobot'] ?? 0);
            }

            if ($kontrol !== 1) {
                $validator->errors()->add('Varian', 'Eksperimen harus punya tepat satu varian kontrol.');
            }

            if ($totalBobot < 1) {
                $validator->errors()->add('Varian', 'Bobot seluruh varian tidak boleh nol; tidak ada yang akan ditetapkan.');
            }
        }];
    }
}
