<?php

declare(strict_types=1);

namespace App\Domain\SiklusAset\Domain\Repositories;

use App\Domain\SiklusAset\Infrastructure\Persistence\Models\SerahTerimaAset;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface SerahTerimaAsetRepository
{
    public function temukan(string $id): ?SerahTerimaAset;
    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;
    public function simpan(SerahTerimaAset $model): SerahTerimaAset;
    public function hapus(SerahTerimaAset $model): void;
}
