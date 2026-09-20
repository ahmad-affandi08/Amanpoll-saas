<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Domain\Repositories;

use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PembayaranPenyedia;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PembayaranPenyediaRepository
{
    public function temukan(string $id): ?PembayaranPenyedia;

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;

    public function simpan(PembayaranPenyedia $model): PembayaranPenyedia;

    public function hapus(PembayaranPenyedia $model): void;
}
