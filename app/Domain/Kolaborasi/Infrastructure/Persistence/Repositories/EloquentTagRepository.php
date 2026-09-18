<?php

declare(strict_types=1);

namespace App\Domain\Kolaborasi\Infrastructure\Persistence\Repositories;

use App\Domain\Kolaborasi\Domain\Repositories\TagRepository;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\Tag;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentTagRepository implements TagRepository
{
    public function temukan(string $id): ?Tag
    {
        return Tag::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return Tag::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(Tag $model): Tag
    {
        $model->save();
        return $model->refresh();
    }

    public function hapus(Tag $model): void
    {
        $model->delete();
    }
}
