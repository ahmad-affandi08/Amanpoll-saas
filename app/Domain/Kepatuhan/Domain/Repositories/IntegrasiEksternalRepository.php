<?php

declare(strict_types=1);

namespace App\Domain\Kepatuhan\Domain\Repositories;

use App\Domain\Kepatuhan\Infrastructure\Persistence\Models\IntegrasiEksternal;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface IntegrasiEksternalRepository
{
    public function temukan(string $id): ?IntegrasiEksternal;
    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;
    public function simpan(IntegrasiEksternal $model): IntegrasiEksternal;
    public function hapus(IntegrasiEksternal $model): void;
}
