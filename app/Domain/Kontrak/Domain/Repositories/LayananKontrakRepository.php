<?php

declare(strict_types=1);

namespace App\Domain\Kontrak\Domain\Repositories;

use App\Domain\Kontrak\Infrastructure\Persistence\Models\LayananKontrak;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface LayananKontrakRepository
{
    public function temukan(string $id): ?LayananKontrak;

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;

    public function simpan(LayananKontrak $model): LayananKontrak;

    public function hapus(LayananKontrak $model): void;
}
