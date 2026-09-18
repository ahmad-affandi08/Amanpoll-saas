<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Core\Organisasi\KonteksOrganisasi;

abstract class PekerjaanOrganisasi
{
    public function __construct(public readonly string $OrganisasiId) {}

    protected function dalamKonteksOrganisasi(callable $callback): mixed
    {
        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($this->OrganisasiId);
        try {
            return $callback();
        } finally {
            $konteks->bersihkan();
        }
    }
}
