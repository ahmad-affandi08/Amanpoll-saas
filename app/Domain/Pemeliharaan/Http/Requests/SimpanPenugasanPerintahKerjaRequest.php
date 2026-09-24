<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Http\Requests;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Pemeliharaan\Application\Actions\TugaskanPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use Closure;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

final class SimpanPenugasanPerintahKerjaRequest extends FormRequest
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
            && Gate::allows('assign', $perintahKerja);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $organisasiId = app(KonteksOrganisasi::class)->wajibId();

        return [
            'PenggunaIds' => ['required', 'array', 'min:1'],
            'PenggunaIds.*' => ['string', 'distinct', Rule::exists('Pengguna', 'Id')->where(fn ($query) => $query->where('OrganisasiId', $organisasiId)->where('Status', 'Aktif')->whereNull('DihapusPada'))],
            'PeranTugas' => ['required', 'string', 'max:60'],
            'GantiPenugasanAktif' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Teknisi yang lingkupnya tidak mencakup perintah kerja ditolak di isiannya,
     * bukan di halaman galat (PRD 8.21). TugaskanPerintahKerja tetap memeriksa
     * ulang sebagai penjaga terakhir.
     *
     * @return list<Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $perintahKerja = $this->route('perintahKerja');
                $penggunaIds = $this->input('PenggunaIds');

                if (! $perintahKerja instanceof PerintahKerja || $validator->errors()->isNotEmpty() || ! is_array($penggunaIds)) {
                    return;
                }

                $alasan = app(TugaskanPerintahKerja::class)->alasanTolakPenerima(
                    $perintahKerja,
                    array_values(array_filter($penggunaIds, 'is_string')),
                );

                if ($alasan !== null) {
                    $validator->errors()->add('PenggunaIds', $alasan);
                }
            },
        ];
    }
}
