<?php

declare(strict_types=1);

namespace App\Domain\Platform\Domain\Repositories;

use App\Domain\Platform\Infrastructure\Persistence\Models\KategoriLokasi;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface KategoriLokasiRepository
{
    public function temukan(string $id): ?KategoriLokasi;

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;

    public function simpan(KategoriLokasi $model): KategoriLokasi;

    public function hapus(KategoriLokasi $model): void;
}
