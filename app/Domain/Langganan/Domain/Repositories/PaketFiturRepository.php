<?php

declare(strict_types=1);

namespace App\Domain\Langganan\Domain\Repositories;

use App\Domain\Langganan\Infrastructure\Persistence\Models\PaketFitur;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PaketFiturRepository
{
    public function temukan(string $id): ?PaketFitur;

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;

    public function simpan(PaketFitur $model): PaketFitur;

    public function hapus(PaketFitur $model): void;
}
