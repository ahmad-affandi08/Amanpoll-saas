<?php

declare(strict_types=1);

namespace App\Domain\Kolaborasi\Application\Actions;

use App\Domain\Kolaborasi\Domain\Repositories\BerkasRepository;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\Berkas;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\LampiranEntitas;
use Illuminate\Support\Facades\Storage;

final class HapusBerkas
{
    public function __construct(private readonly BerkasRepository $berkasRepository) {}

    public function jalankan(Berkas $berkas): void
    {
        LampiranEntitas::query()->where('BerkasId', $berkas->Id)->delete();
        Storage::disk($berkas->MediaPenyimpanan)->delete($berkas->LokasiPenyimpanan);
        $this->berkasRepository->hapus($berkas);
    }
}
