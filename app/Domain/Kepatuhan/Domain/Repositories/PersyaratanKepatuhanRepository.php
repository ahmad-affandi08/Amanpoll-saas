<?php

declare(strict_types=1);

namespace App\Domain\Kepatuhan\Domain\Repositories;

use App\Domain\Kepatuhan\Infrastructure\Persistence\Models\PersyaratanKepatuhan;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PersyaratanKepatuhanRepository
{
    public function temukan(string $id): ?PersyaratanKepatuhan;

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;

    public function simpan(PersyaratanKepatuhan $model): PersyaratanKepatuhan;

    public function hapus(PersyaratanKepatuhan $model): void;
}
