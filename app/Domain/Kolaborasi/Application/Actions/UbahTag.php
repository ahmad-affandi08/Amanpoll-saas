<?php

declare(strict_types=1);

namespace App\Domain\Kolaborasi\Application\Actions;

use App\Domain\Kolaborasi\Domain\Repositories\TagRepository;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\Tag;

final class UbahTag
{
    public function __construct(private readonly TagRepository $tagRepository) {}

    /**
     * @param array<string, mixed> $data
     */
    public function jalankan(Tag $tag, array $data): Tag
    {
        $tag->fill($data);

        return $this->tagRepository->simpan($tag);
    }
}
