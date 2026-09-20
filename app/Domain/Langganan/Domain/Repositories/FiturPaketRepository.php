<?php

declare(strict_types=1);

namespace App\Domain\Langganan\Domain\Repositories;

use App\Domain\Langganan\Infrastructure\Persistence\Models\FiturPaket;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface FiturPaketRepository
{
    public function temukan(string $id): ?FiturPaket;

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;

    public function simpan(FiturPaket $model): FiturPaket;

    public function hapus(FiturPaket $model): void;
}
