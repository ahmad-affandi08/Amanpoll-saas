<?php

declare(strict_types=1);

namespace App\Domain\Penyedia\Domain\Repositories;

use App\Domain\Penyedia\Infrastructure\Persistence\Models\PenilaianPenyedia;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PenilaianPenyediaRepository
{
    public function temukan(string $id): ?PenilaianPenyedia;
    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;
    public function simpan(PenilaianPenyedia $model): PenilaianPenyedia;
    public function hapus(PenilaianPenyedia $model): void;
}
