<?php

declare(strict_types=1);

namespace App\Domain\Aset\Domain\Repositories;

use App\Domain\Aset\Infrastructure\Persistence\Models\RiwayatPenanggungJawabAset;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface RiwayatPenanggungJawabAsetRepository
{
    public function temukan(string $id): ?RiwayatPenanggungJawabAset;

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;

    public function simpan(RiwayatPenanggungJawabAset $model): RiwayatPenanggungJawabAset;

    public function hapus(RiwayatPenanggungJawabAset $model): void;
}
