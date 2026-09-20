<?php

declare(strict_types=1);

namespace App\Domain\PreventifInspeksi\Domain\Repositories;

use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\PelaksanaanDaftarPeriksa;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PelaksanaanDaftarPeriksaRepository
{
    public function temukan(string $id): ?PelaksanaanDaftarPeriksa;

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;

    public function simpan(PelaksanaanDaftarPeriksa $model): PelaksanaanDaftarPeriksa;

    public function hapus(PelaksanaanDaftarPeriksa $model): void;
}
