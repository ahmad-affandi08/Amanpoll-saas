<?php

declare(strict_types=1);

namespace App\Domain\Platform\Infrastructure\Persistence\Repositories;

use App\Domain\Platform\Domain\Repositories\OrganisasiRepository;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentOrganisasiRepository implements OrganisasiRepository
{
    public function temukan(string $id): ?Organisasi
    {
        return Organisasi::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return Organisasi::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(Organisasi $model): Organisasi
    {
        $model->save();
        return $model->refresh();
    }

    public function hapus(Organisasi $model): void
    {
        $model->delete();
    }
}
