<?php

declare(strict_types=1);

namespace App\Domain\Kalibrasi\Domain\Repositories;

use App\Domain\Kalibrasi\Infrastructure\Persistence\Models\RencanaKalibrasi;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface RencanaKalibrasiRepository
{
    public function temukan(string $id): ?RencanaKalibrasi;

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;

    public function simpan(RencanaKalibrasi $model): RencanaKalibrasi;

    public function hapus(RencanaKalibrasi $model): void;
}
