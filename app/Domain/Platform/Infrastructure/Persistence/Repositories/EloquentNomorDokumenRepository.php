<?php

declare(strict_types=1);

namespace App\Domain\Platform\Infrastructure\Persistence\Repositories;

use App\Domain\Platform\Domain\Repositories\NomorDokumenRepository;
use App\Domain\Platform\Infrastructure\Persistence\Models\NomorDokumen;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentNomorDokumenRepository implements NomorDokumenRepository
{
    public function temukan(string $id): ?NomorDokumen
    {
        return NomorDokumen::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return NomorDokumen::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(NomorDokumen $model): NomorDokumen
    {
        $model->save();
        return $model->refresh();
    }

    public function hapus(NomorDokumen $model): void
    {
        $model->delete();
    }
}
