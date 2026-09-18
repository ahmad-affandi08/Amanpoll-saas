<?php

declare(strict_types=1);

namespace App\Domain\Platform\Domain\Repositories;

use App\Domain\Platform\Infrastructure\Persistence\Models\NomorDokumen;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface NomorDokumenRepository
{
    public function temukan(string $id): ?NomorDokumen;
    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;
    public function simpan(NomorDokumen $model): NomorDokumen;
    public function hapus(NomorDokumen $model): void;
}
