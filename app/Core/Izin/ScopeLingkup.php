<?php

declare(strict_types=1);

namespace App\Core\Izin;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/**
 * Menyaring baris menurut unit dan ruangan yang boleh dilihat pengguna.
 *
 * Berjalan di atas ScopeOrganisasi, bukan menggantikannya: tenancy tetap
 * lapisan pertama, ini lapisan kedua di dalam satu tenant.
 *
 * Hanya berlaku bila ada pengguna web yang sedang masuk. Perintah artisan,
 * antrian, dan konsol platform berjalan tanpa pengguna dan tidak dibatasi --
 * mereka sudah dibatasi tenancy, dan membatasinya lagi hanya akan mematikan
 * pekerjaan terjadwal tanpa menambah keamanan.
 *
 * @implements Scope<Model>
 */
final class ScopeLingkup implements Scope
{
    /**
     * @param  Builder<covariant Model>  $builder
     */
    public function apply(Builder $builder, Model $model): void
    {
        // Model yang tidak menyatakan petanya tidak dapat disaring; membiarkan
        // kuerinya lewat lebih jujur daripada menebak kolomnya.
        if (! $model instanceof BerlingkupUnit) {
            return;
        }

        $pengguna = Auth::guard('web')->user();

        if ($pengguna === null) {
            return;
        }

        $penggunaId = (string) $pengguna->getAuthIdentifier();
        $lingkup = app(LingkupAkses::class);

        if ($lingkup->tanpaBatas($penggunaId)) {
            return;
        }

        $diizinkan = [
            'unit' => $lingkup->unitDiizinkan($penggunaId),
            'lokasi' => $lingkup->lokasiDiizinkan($penggunaId),
        ];

        $kolomLingkup = $model->kolomLingkup();

        $builder->where(function (Builder $kueri) use ($model, $kolomLingkup, $diizinkan): void {
            $adaSyarat = false;

            foreach ($kolomLingkup as $kolom => $jenis) {
                $nilai = $diizinkan[$jenis];

                if ($nilai === []) {
                    continue;
                }

                $kueri->orWhereIn($model->qualifyColumn($kolom), $nilai);
                $adaSyarat = true;
            }

            // Fail-closed: pengguna berlingkup yang tidak punya satu pun unit
            // atau ruangan yang cocok tidak melihat apa pun, bukan melihat semua.
            if (! $adaSyarat) {
                $kueri->whereRaw('1 = 0');
            }
        });
    }
}
