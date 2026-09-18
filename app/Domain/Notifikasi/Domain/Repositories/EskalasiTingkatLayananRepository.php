<?php

declare(strict_types=1);

namespace App\Domain\Notifikasi\Domain\Repositories;

use App\Domain\Notifikasi\Infrastructure\Persistence\Models\EskalasiTingkatLayanan;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface EskalasiTingkatLayananRepository
{
    public function temukan(string $id): ?EskalasiTingkatLayanan;
    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;
    public function simpan(EskalasiTingkatLayanan $model): EskalasiTingkatLayanan;
    public function hapus(EskalasiTingkatLayanan $model): void;
}
