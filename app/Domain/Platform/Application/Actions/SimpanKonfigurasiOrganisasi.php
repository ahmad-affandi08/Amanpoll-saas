<?php

declare(strict_types=1);

namespace App\Domain\Platform\Application\Actions;

use App\Core\Konfigurasi\LayananKonfigurasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\KonfigurasiOrganisasi;

final class SimpanKonfigurasiOrganisasi
{
    public function __construct(private readonly LayananKonfigurasi $layananKonfigurasi) {}

    public function jalankan(string $organisasiId, string $kunci, mixed $nilai): void
    {
        KonfigurasiOrganisasi::query()->updateOrCreate(
            ['OrganisasiId' => $organisasiId, 'Kunci' => $kunci],
            ['Nilai' => $nilai],
        );

        $this->layananKonfigurasi->bersihkanCache($organisasiId, $kunci);
    }
}
