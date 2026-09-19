<?php

declare(strict_types=1);

namespace App\Domain\Kolaborasi\Application\Actions;

use App\Domain\Kolaborasi\Domain\Repositories\LampiranEntitasRepository;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\LampiranEntitas;

final class LepaskanLampiran
{
    public function __construct(private readonly LampiranEntitasRepository $lampiranEntitasRepository) {}

    public function jalankan(LampiranEntitas $lampiranEntitas): void
    {
        $this->lampiranEntitasRepository->hapus($lampiranEntitas);
    }
}
