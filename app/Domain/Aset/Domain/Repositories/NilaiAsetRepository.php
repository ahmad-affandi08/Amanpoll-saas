<?php

declare(strict_types=1);

namespace App\Domain\Aset\Domain\Repositories;

use App\Domain\Aset\Infrastructure\Persistence\Models\NilaiAset;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface NilaiAsetRepository
{
    public function temukan(string $id): ?NilaiAset;
    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;
    public function simpan(NilaiAset $model): NilaiAset;
    public function hapus(NilaiAset $model): void;
}
