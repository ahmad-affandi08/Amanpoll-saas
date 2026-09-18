<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Domain\Repositories;

use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PenawaranPenyedia;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PenawaranPenyediaRepository
{
    public function temukan(string $id): ?PenawaranPenyedia;
    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;
    public function simpan(PenawaranPenyedia $model): PenawaranPenyedia;
    public function hapus(PenawaranPenyedia $model): void;
}
