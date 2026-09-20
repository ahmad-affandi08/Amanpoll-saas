<?php

declare(strict_types=1);

namespace App\Domain\PreventifInspeksi\Domain\Repositories;

use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\TemplatInspeksi;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface TemplatInspeksiRepository
{
    public function temukan(string $id): ?TemplatInspeksi;

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;

    public function simpan(TemplatInspeksi $model): TemplatInspeksi;

    public function hapus(TemplatInspeksi $model): void;
}
