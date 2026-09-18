<?php

declare(strict_types=1);

namespace App\Domain\Langganan\Domain\Repositories;

use App\Domain\Langganan\Infrastructure\Persistence\Models\PaketLangganan;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PaketLanggananRepository
{
    public function temukan(string $id): ?PaketLangganan;
    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;
    public function simpan(PaketLangganan $model): PaketLangganan;
    public function hapus(PaketLangganan $model): void;
}
