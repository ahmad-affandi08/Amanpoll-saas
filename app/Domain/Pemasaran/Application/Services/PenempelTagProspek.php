<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Application\Services;

use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Prospek;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\TagProspek;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Menempelkan tag pada prospek (MARKETING.md 10, 24). */
final class PenempelTagProspek
{
    /** @param list<string> $nama */
    public function tempel(Prospek $prospek, array $nama): void
    {
        foreach ($nama as $satu) {
            $bersih = trim($satu);

            if ($bersih === '') {
                continue;
            }

            $tag = TagProspek::query()->firstOrCreate(['Nama' => $bersih]);

            DB::table('ProspekTag')->insertOrIgnore([
                'Id' => (string) Str::ulid(),
                'ProspekId' => $prospek->Id,
                'TagProspekId' => $tag->Id,
                'DibuatPada' => now(),
            ]);
        }
    }
}
