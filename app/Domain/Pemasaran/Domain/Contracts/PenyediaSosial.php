<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Domain\Contracts;

use App\Domain\Pemasaran\Domain\Enums\ChannelSosial;
use App\Domain\Pemasaran\Domain\ValueObjects\HasilPosSosial;
use App\Domain\Pemasaran\Domain\ValueObjects\PosSosial;

/** Abstraksi penyedia penjadwal sosial, misalnya Metricool (MARKETING.md 18, 28). */
interface PenyediaSosial
{
    public function kode(): string;

    /** @return list<ChannelSosial> */
    public function channelDidukung(): array;

    public function terbitkan(PosSosial $pos): HasilPosSosial;
}
