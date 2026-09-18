<?php

declare(strict_types=1);

namespace App\Domain\Platform\Application\Actions;

use App\Domain\Platform\Domain\Repositories\PeranRepository;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Illuminate\Support\Facades\DB;

final class HapusPeran
{
    public function __construct(private readonly PeranRepository $peranRepository) {}

    public function jalankan(Peran $peran): void
    {
        if ($peran->BawaanSistem) {
            throw new AturanBisnisDilanggar('Peran bawaan sistem tidak dapat dihapus.');
        }

        if (DB::table('PenggunaPeran')->where('PeranId', $peran->Id)->exists()) {
            throw new AturanBisnisDilanggar('Peran masih dipakai oleh pengguna dan tidak dapat dihapus.');
        }

        $this->peranRepository->hapus($peran);
    }
}
