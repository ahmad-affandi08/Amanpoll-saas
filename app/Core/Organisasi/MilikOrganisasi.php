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

            if (empty($model->OrganisasiId)) {
                if ($konteks->ada()) {
                    $model->OrganisasiId = $konteks->wajibId();
                }
            } elseif ($konteks->ada() && (string) $model->OrganisasiId !== $konteks->wajibId()) {
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
