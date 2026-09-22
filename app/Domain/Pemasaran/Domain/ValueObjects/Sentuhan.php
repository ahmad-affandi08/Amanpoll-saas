<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Domain\ValueObjects;

use Carbon\CarbonImmutable;

/** Satu sentuhan kampanye pada perjalanan seorang pengunjung (MARKETING.md 14). */
final readonly class Sentuhan
{
    public function __construct(
        public string $sumber,
        public ?string $medium,
        public ?string $kampanye,
        public ?string $kampanyeId,
        public CarbonImmutable $pada,
    ) {}

    /** Kunjungan berurutan dari sumber yang sama adalah satu sentuhan, bukan banyak. */
    public function kunci(): string
    {
        return $this->sumber.'|'.($this->medium ?? '').'|'.($this->kampanye ?? '');
    }

    /** @return array<string, mixed> */
    public function keArray(): array
    {
        return [
            'Sumber' => $this->sumber,
            'Medium' => $this->medium,
            'Kampanye' => $this->kampanye,
            'KampanyeId' => $this->kampanyeId,
            'Pada' => $this->pada->toIso8601String(),
        ];
    }
}
