<?php

declare(strict_types=1);

namespace App\Domain\Platform\Domain\Repositories;

use App\Domain\Platform\Infrastructure\Persistence\Models\KunciApi;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface KunciApiRepository
{
    public function temukan(string $id): ?KunciApi;
    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;
    public function simpan(KunciApi $model): KunciApi;
    public function hapus(KunciApi $model): void;
}
