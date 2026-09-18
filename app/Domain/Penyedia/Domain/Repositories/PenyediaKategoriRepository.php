<?php

declare(strict_types=1);

namespace App\Domain\Penyedia\Domain\Repositories;

use App\Domain\Penyedia\Infrastructure\Persistence\Models\PenyediaKategori;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PenyediaKategoriRepository
{
    public function temukan(string $id): ?PenyediaKategori;
    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;
    public function simpan(PenyediaKategori $model): PenyediaKategori;
    public function hapus(PenyediaKategori $model): void;
}
