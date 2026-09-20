<?php

declare(strict_types=1);

namespace App\Domain\Platform\Infrastructure\Persistence\Repositories;

use App\Domain\Platform\Domain\Repositories\UnitOrganisasiRepository;
use App\Domain\Platform\Infrastructure\Persistence\Models\UnitOrganisasi;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentUnitOrganisasiRepository implements UnitOrganisasiRepository
{
    public function temukan(string $id): ?UnitOrganisasi
    {
        return UnitOrganisasi::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return UnitOrganisasi::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(UnitOrganisasi $model): UnitOrganisasi
    {
        $model->save();

        return $model->refresh();
    }

    public function hapus(UnitOrganisasi $model): void
    {
        $model->delete();
    }
}
