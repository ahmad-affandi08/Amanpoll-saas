<?php

declare(strict_types=1);

namespace App\Domain\Langganan\Domain\Repositories;

use App\Domain\Langganan\Infrastructure\Persistence\Models\TagihanLangganan;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface TagihanLanggananRepository
{
    public function temukan(string $id): ?TagihanLangganan;
    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;
    public function simpan(TagihanLangganan $model): TagihanLangganan;
    public function hapus(TagihanLangganan $model): void;
}
