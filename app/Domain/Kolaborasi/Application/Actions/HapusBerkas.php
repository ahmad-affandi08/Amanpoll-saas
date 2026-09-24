<?php

declare(strict_types=1);

namespace App\Domain\Kolaborasi\Application\Actions;

use App\Domain\Kolaborasi\Application\Services\PenyimpanBerkas;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\Berkas;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\LampiranEntitas;

final class HapusBerkas
{
    public function __construct(private readonly PenyimpanBerkas $penyimpan) {}

    /** Salinan fisik baru hilang bila tidak ada `Berkas` lain yang berbagi salinan itu. */
    public function jalankan(Berkas $berkas): void
    {
        LampiranEntitas::query()->where('BerkasId', $berkas->Id)->delete();
        $this->penyimpan->hapus($berkas);
    }
}
