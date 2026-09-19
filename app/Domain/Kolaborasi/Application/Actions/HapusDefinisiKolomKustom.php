<?php

declare(strict_types=1);

namespace App\Domain\Kolaborasi\Application\Actions;

use App\Domain\Kolaborasi\Domain\Repositories\DefinisiKolomKustomRepository;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\DefinisiKolomKustom;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\NilaiKolomKustom;

final class HapusDefinisiKolomKustom
{
    public function __construct(private readonly DefinisiKolomKustomRepository $definisiKolomKustomRepository) {}

    public function jalankan(DefinisiKolomKustom $definisiKolomKustom): void
    {
        NilaiKolomKustom::query()->where('DefinisiKolomKustomId', $definisiKolomKustom->Id)->delete();
        $this->definisiKolomKustomRepository->hapus($definisiKolomKustom);
    }
}
