<?php

declare(strict_types=1);

namespace Tests\Dukungan;

use App\Domain\Pemasaran\Domain\Contracts\PenyediaSosial;
use App\Domain\Pemasaran\Domain\Enums\ChannelSosial;
use App\Domain\Pemasaran\Domain\ValueObjects\HasilPosSosial;
use App\Domain\Pemasaran\Domain\ValueObjects\PosSosial;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Illuminate\Support\Str;

/** Penyedia sosial palsu: mencatat apa yang diterbitkan, dan dapat disuruh gagal (MARKETING.md 28). */
final class PenyediaSosialPalsu implements PenyediaSosial
{
    /** @var list<PosSosial> */
    public array $diterbitkan = [];

    public bool $gagalkan = false;

    public function kode(): string
    {
        return 'Palsu';
    }

    /** @return list<ChannelSosial> */
    public function channelDidukung(): array
    {
        return ChannelSosial::cases();
    }

    public function terbitkan(PosSosial $pos): HasilPosSosial
    {
        if ($this->gagalkan) {
            throw new AturanBisnisDilanggar('Penyedia palsu sengaja menolak.');
        }

        $this->diterbitkan[] = $pos;

        return new HasilPosSosial((string) Str::ulid(), 'https://contoh.test/post/'.count($this->diterbitkan));
    }

    /** @return list<string> */
    public function channelTerbit(): array
    {
        return array_map(fn (PosSosial $satu): string => $satu->channel->value, $this->diterbitkan);
    }
}
