<?php

declare(strict_types=1);

namespace App\Domain\Platform\Domain\Repositories;

use App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface LokasiRepository
{
    public function temukan(string $id): ?Lokasi;

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;

    public function simpan(Lokasi $model): Lokasi;

    public function hapus(Lokasi $model): void;
}
