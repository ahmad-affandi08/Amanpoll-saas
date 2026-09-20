<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Domain\Repositories;

use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\TagihanPenyedia;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface TagihanPenyediaRepository
{
    public function temukan(string $id): ?TagihanPenyedia;

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;

    public function simpan(TagihanPenyedia $model): TagihanPenyedia;

    public function hapus(TagihanPenyedia $model): void;
}
