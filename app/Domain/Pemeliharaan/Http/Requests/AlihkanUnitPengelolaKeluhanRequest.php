<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Http\Requests;

use App\Domain\Pemeliharaan\Application\Actions\AlihkanUnitPengelolaKeluhan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\Keluhan;
use App\Domain\Platform\Http\Requests\UnitPengelolaSah;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/** Alihkan keluhan ke unit pengelola lain (PRD 8.21): unit tujuan sah dan alasan wajib. */
final class AlihkanUnitPengelolaKeluhanRequest extends FormRequest
{
    /** Izin diperiksa sebelum validasi: tanpa `Keluhan.Kelola` jawabannya 403, bukan galat isian. */
    public function authorize(): bool
    {
        $keluhan = $this->route('keluhan');

        return $keluhan instanceof Keluhan && $this->user()?->can('alihkanUnitPengelola', $keluhan) === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'UnitPengelolaId' => ['required', 'string', new UnitPengelolaSah],
            'Alasan' => ['required', 'string', 'max:2000'],
            'Versi' => ['required', 'integer', 'min:1'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['UnitPengelolaId.required' => 'Pilih unit pengelola tujuan.'];
    }

    /**
     * Hambatan keluhan (final, unit yang sama, perintah kerja aktif) sebagai galat
     * isian yang terbaca di dialog, bukan halaman galat 422 dari Action.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $keluhan = $this->route('keluhan');
            if (! $keluhan instanceof Keluhan || $validator->errors()->has('UnitPengelolaId')) {
                return;
            }

            $hambatan = app(AlihkanUnitPengelolaKeluhan::class)->hambatan($keluhan, $this->string('UnitPengelolaId')->toString());
            if ($hambatan !== null) {
                $validator->errors()->add('UnitPengelolaId', $hambatan);
            }
        }];
    }
}
