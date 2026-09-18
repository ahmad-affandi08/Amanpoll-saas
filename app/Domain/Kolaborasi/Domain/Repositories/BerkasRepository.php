<?php

declare(strict_types=1);

namespace App\Domain\Kolaborasi\Domain\Repositories;

use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\Berkas;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface BerkasRepository
{
    public function temukan(string $id): ?Berkas;
    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;
    public function simpan(Berkas $model): Berkas;
    public function hapus(Berkas $model): void;
}
