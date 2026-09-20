<?php

declare(strict_types=1);

namespace App\Domain\Langganan\Domain\Repositories;

use App\Domain\Langganan\Infrastructure\Persistence\Models\Langganan;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface LanggananRepository
{
    public function temukan(string $id): ?Langganan;

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;

    public function simpan(Langganan $model): Langganan;

    public function hapus(Langganan $model): void;
}
