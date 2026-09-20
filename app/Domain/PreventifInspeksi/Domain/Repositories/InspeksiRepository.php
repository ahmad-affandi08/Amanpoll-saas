<?php

declare(strict_types=1);

namespace App\Domain\PreventifInspeksi\Domain\Repositories;

use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\Inspeksi;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface InspeksiRepository
{
    public function temukan(string $id): ?Inspeksi;

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;

    public function simpan(Inspeksi $model): Inspeksi;

    public function hapus(Inspeksi $model): void;
}
