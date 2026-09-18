<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Domain\Repositories;

use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\AturanTingkatLayanan;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface AturanTingkatLayananRepository
{
    public function temukan(string $id): ?AturanTingkatLayanan;
    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;
    public function simpan(AturanTingkatLayanan $model): AturanTingkatLayanan;
    public function hapus(AturanTingkatLayanan $model): void;
}
