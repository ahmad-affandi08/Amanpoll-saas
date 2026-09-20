<?php

declare(strict_types=1);

namespace App\Domain\Kolaborasi\Domain\Repositories;

use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\DefinisiKolomKustom;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface DefinisiKolomKustomRepository
{
    public function temukan(string $id): ?DefinisiKolomKustom;

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;

    public function simpan(DefinisiKolomKustom $model): DefinisiKolomKustom;

    public function hapus(DefinisiKolomKustom $model): void;
}
