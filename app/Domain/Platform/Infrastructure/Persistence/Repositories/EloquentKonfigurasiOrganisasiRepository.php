<?php

declare(strict_types=1);

namespace App\Domain\Platform\Infrastructure\Persistence\Repositories;

use App\Domain\Platform\Domain\Repositories\KonfigurasiOrganisasiRepository;
use App\Domain\Platform\Infrastructure\Persistence\Models\KonfigurasiOrganisasi;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentKonfigurasiOrganisasiRepository implements KonfigurasiOrganisasiRepository
{
    public function temukan(string $id): ?KonfigurasiOrganisasi
    {
        return KonfigurasiOrganisasi::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return KonfigurasiOrganisasi::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(KonfigurasiOrganisasi $model): KonfigurasiOrganisasi
    {
        $model->save();
        return $model->refresh();
    }

    public function hapus(KonfigurasiOrganisasi $model): void
    {
        $model->delete();
    }
}
