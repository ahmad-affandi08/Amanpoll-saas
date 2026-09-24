<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Http\Requests;

use App\Domain\Pemeliharaan\Application\Actions\AlihkanUnitPengelolaPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use App\Domain\Platform\Http\Requests\UnitPengelolaSah;
use Closure;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

final class AlihkanUnitPengelolaPerintahKerjaRequest extends FormRequest
{
    /**
     * Diperiksa sebelum validasi: pesan penolakan di after() menyebut nama
     * pengguna lain, jadi tidak boleh sampai ke yang tidak berhak.
     */
    public function authorize(): bool
    {
        $perintahKerja = $this->route('perintahKerja');

        return $this->user() !== null
            && $perintahKerja instanceof PerintahKerja
            && Gate::allows('alihkanUnitPengelola', $perintahKerja);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'UnitPengelolaId' => UnitPengelolaSah::aturan(),
            'Alasan' => ['required', 'string', 'max:500'],
        ];
    }

    /**
     * Penolakan pengalihan (tiket final, unit sama, penugasan aktif yang akan
     * kehilangan tiketnya) tampil di isiannya, bukan halaman galat. Action
     * tetap memeriksa ulang sebagai penjaga terakhir.
     *
     * @return list<Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $perintahKerja = $this->route('perintahKerja');
                $tujuan = $this->input('UnitPengelolaId');

                if (! $perintahKerja instanceof PerintahKerja || $validator->errors()->has('UnitPengelolaId')) {
                    return;
                }

                $alasan = app(AlihkanUnitPengelolaPerintahKerja::class)->alasanDitolak(
                    $perintahKerja,
                    is_string($tujuan) ? $tujuan : null,
                );

                if ($alasan !== null) {
                    $validator->errors()->add('UnitPengelolaId', $alasan);
                }
            },
        ];
    }
}
