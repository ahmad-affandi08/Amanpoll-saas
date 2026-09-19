<?php

declare(strict_types=1);

namespace App\Domain\Kolaborasi\Application\Actions;

use App\Domain\Kolaborasi\Domain\Repositories\TagRepository;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\EntitasTag;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\Tag;

final class HapusTag
{
    public function __construct(private readonly TagRepository $tagRepository) {}

    public function jalankan(Tag $tag): void
    {
        EntitasTag::query()->where('TagId', $tag->Id)->delete();
        $this->tagRepository->hapus($tag);
    }
}
