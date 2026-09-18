<?php

declare(strict_types=1);

namespace App\Domain\Platform\Domain\Repositories;

use App\Domain\Platform\Infrastructure\Persistence\Models\PerangkatPengguna;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PerangkatPenggunaRepository
{
    public function temukan(string $id): ?PerangkatPengguna;
    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;
    public function simpan(PerangkatPengguna $model): PerangkatPengguna;
    public function hapus(PerangkatPengguna $model): void;
}
