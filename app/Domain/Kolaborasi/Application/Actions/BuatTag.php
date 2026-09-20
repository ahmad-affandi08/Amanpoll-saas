<?php

declare(strict_types=1);

namespace App\Domain\Kolaborasi\Application\Actions;

use App\Domain\Kolaborasi\Domain\Repositories\TagRepository;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\Tag;

final class BuatTag
{
    public function __construct(private readonly TagRepository $tagRepository) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function jalankan(array $data): Tag
    {
        return $this->tagRepository->simpan(new Tag($data));
    }
}
