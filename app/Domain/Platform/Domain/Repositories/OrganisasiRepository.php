<?php

declare(strict_types=1);

namespace App\Domain\Platform\Domain\Repositories;

use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface OrganisasiRepository
{
    public function temukan(string $id): ?Organisasi;

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;

    public function simpan(Organisasi $model): Organisasi;

    public function hapus(Organisasi $model): void;
}
