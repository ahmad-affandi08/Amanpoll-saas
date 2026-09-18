<?php

declare(strict_types=1);

namespace App\Core\Organisasi;

use Illuminate\Database\Eloquent\Model;

trait MilikOrganisasi
{
    protected static function bootMilikOrganisasi(): void
    {
        static::addGlobalScope(new ScopeOrganisasi());

        static::creating(function (Model $model): void {
            if (empty($model->OrganisasiId)) {
                $konteks = app(KonteksOrganisasi::class);
                if ($konteks->ada()) {
                    $model->OrganisasiId = $konteks->wajibId();
                }
            }

            PemeriksaRelasiOrganisasi::pastikanSeorganisasi($model);
        });

        static::updating(function (Model $model): void {
            PemeriksaRelasiOrganisasi::pastikanSeorganisasi($model);
        });
    }
}
