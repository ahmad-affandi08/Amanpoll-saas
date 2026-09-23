<?php

declare(strict_types=1);

namespace App\Core\Organisasi;

use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Illuminate\Database\Eloquent\Model;

trait MilikOrganisasi
{
    protected static function bootMilikOrganisasi(): void
    {
        static::addGlobalScope(new ScopeOrganisasi);

        static::creating(function (Model $model): void {
            $konteks = app(KonteksOrganisasi::class);

            // Dibaca lewat getAttribute/setAttribute, bukan properti ajaib:
            // parameternya bertipe Model, sehingga PHPStan tidak dapat tahu
            // kolom OrganisasiId ada pada model yang memakai trait ini.
            $milik = $model->getAttribute('OrganisasiId');

            if (blank($milik)) {
                if ($konteks->ada()) {
                    $model->setAttribute('OrganisasiId', $konteks->wajibId());
                }
            } elseif ($konteks->ada() && (string) $milik !== $konteks->wajibId()) {
                // Tenant yang sedang berjalan tidak boleh menulis ke tenant lain.
                throw new AturanBisnisDilanggar(
                    'OrganisasiId tidak boleh menunjuk organisasi lain dari yang sedang aktif.',
                );
            }

            PemeriksaRelasiOrganisasi::pastikanSeorganisasi($model);
        });

        static::updating(function (Model $model): void {
            PemeriksaRelasiOrganisasi::pastikanSeorganisasi($model);
        });
    }
}
