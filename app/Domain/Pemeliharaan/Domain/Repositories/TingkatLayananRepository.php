<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Domain\Repositories;

use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\TingkatLayanan;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface TingkatLayananRepository
{
    public function temukan(string $id): ?TingkatLayanan;
    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;
    public function simpan(TingkatLayanan $model): TingkatLayanan;
    public function hapus(TingkatLayanan $model): void;
}
