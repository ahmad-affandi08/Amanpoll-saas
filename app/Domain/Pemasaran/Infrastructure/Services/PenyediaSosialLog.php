<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Infrastructure\Services;

use App\Domain\Pemasaran\Domain\Contracts\PenyediaSosial;
use App\Domain\Pemasaran\Domain\Enums\ChannelSosial;
use App\Domain\Pemasaran\Domain\ValueObjects\HasilPosSosial;
use App\Domain\Pemasaran\Domain\ValueObjects\PosSosial;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/** Penyedia bawaan yang hanya menulis ke log: adapter nyata menyusul (MARKETING.md 18, 28). */
final class PenyediaSosialLog implements PenyediaSosial
{
    public function kode(): string
    {
        return 'Log';
    }

    /** @return list<ChannelSosial> */
    public function channelDidukung(): array
    {
        return ChannelSosial::cases();
    }

    public function terbitkan(PosSosial $pos): HasilPosSosial
    {
        Log::info('Posting sosial ditulis ke log, bukan diterbitkan.', [
            'Channel' => $pos->channel->value,
            'Kunci' => $pos->kunciIdempotensi,
            'Tautan' => $pos->tautan,
        ]);

        return new HasilPosSosial((string) Str::ulid());
    }
}
