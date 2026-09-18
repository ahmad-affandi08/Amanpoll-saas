<?php

declare(strict_types=1);

namespace App\Domain\Platform\Infrastructure\Persistence\Repositories;

use App\Domain\Platform\Domain\Repositories\KunciApiRepository;
use App\Domain\Platform\Infrastructure\Persistence\Models\KunciApi;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentKunciApiRepository implements KunciApiRepository
{
    public function temukan(string $id): ?KunciApi
    {
        return KunciApi::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return KunciApi::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(KunciApi $model): KunciApi
    {
        $model->save();
        return $model->refresh();
    }

    public function hapus(KunciApi $model): void
    {
        $model->delete();
    }
}
