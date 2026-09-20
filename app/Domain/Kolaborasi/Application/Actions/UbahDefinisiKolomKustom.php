<?php

declare(strict_types=1);

namespace App\Domain\Kolaborasi\Application\Actions;

use App\Domain\Kolaborasi\Domain\Repositories\DefinisiKolomKustomRepository;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\DefinisiKolomKustom;

final class UbahDefinisiKolomKustom
{
    public function __construct(private readonly DefinisiKolomKustomRepository $definisiKolomKustomRepository) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function jalankan(DefinisiKolomKustom $definisiKolomKustom, array $data): DefinisiKolomKustom
    {
        $definisiKolomKustom->fill($data);

        return $this->definisiKolomKustomRepository->simpan($definisiKolomKustom);
    }
}
