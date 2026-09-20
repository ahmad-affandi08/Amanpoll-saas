<?php

declare(strict_types=1);

namespace App\Domain\Platform\Domain\Repositories;

use App\Domain\Platform\Infrastructure\Persistence\Models\UnitOrganisasi;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface UnitOrganisasiRepository
{
    public function temukan(string $id): ?UnitOrganisasi;

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;

    public function simpan(UnitOrganisasi $model): UnitOrganisasi;

    public function hapus(UnitOrganisasi $model): void;
}
