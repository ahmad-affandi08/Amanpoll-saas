<?php

declare(strict_types=1);

namespace App\Domain\Pelaporan\Domain\Repositories;

use App\Domain\Pelaporan\Infrastructure\Persistence\Models\DasborTersimpan;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface DasborTersimpanRepository
{
    public function temukan(string $id): ?DasborTersimpan;

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;

    public function simpan(DasborTersimpan $model): DasborTersimpan;

    public function hapus(DasborTersimpan $model): void;
}
