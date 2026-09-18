<?php

declare(strict_types=1);

namespace App\Domain\Kolaborasi\Domain\Repositories;

use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\NilaiKolomKustom;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface NilaiKolomKustomRepository
{
    public function temukan(string $id): ?NilaiKolomKustom;
    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;
    public function simpan(NilaiKolomKustom $model): NilaiKolomKustom;
    public function hapus(NilaiKolomKustom $model): void;
}
