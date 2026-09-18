<?php

declare(strict_types=1);

namespace App\Core\Organisasi;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

final class ScopeOrganisasi implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $konteks = app(KonteksOrganisasi::class);

        if (!$konteks->ada()) {
            // Fail-closed: query tenant tidak boleh lintas organisasi secara tidak sengaja.
            $builder->whereRaw('1 = 0');
            return;
        }

        $builder->where($model->qualifyColumn('OrganisasiId'), $konteks->wajibId());
    }
}
