<?php

declare(strict_types=1);

namespace App\Domain\Notifikasi\Domain\Repositories;

use App\Domain\Notifikasi\Infrastructure\Persistence\Models\Notifikasi;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface NotifikasiRepository
{
    public function temukan(string $id): ?Notifikasi;
    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;
    public function simpan(Notifikasi $model): Notifikasi;
    public function hapus(Notifikasi $model): void;
}
