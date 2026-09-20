<?php

declare(strict_types=1);

namespace App\Domain\Kolaborasi\Domain\Repositories;

use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\Tag;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface TagRepository
{
    public function temukan(string $id): ?Tag;

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;

    public function simpan(Tag $model): Tag;

    public function hapus(Tag $model): void;
}
