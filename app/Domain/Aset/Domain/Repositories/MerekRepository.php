<?php

declare(strict_types=1);

namespace App\Domain\Aset\Domain\Repositories;

use App\Domain\Aset\Infrastructure\Persistence\Models\Merek;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface MerekRepository
{
    public function temukan(string $id): ?Merek;
    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;
    public function simpan(Merek $model): Merek;
    public function hapus(Merek $model): void;
}
