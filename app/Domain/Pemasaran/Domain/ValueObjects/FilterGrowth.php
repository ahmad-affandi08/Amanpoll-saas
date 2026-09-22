<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Domain\ValueObjects;

use Carbon\CarbonImmutable;

/** Sembilan penyaring dashboard growth (MARKETING.md 5). */
final readonly class FilterGrowth
{
    public function __construct(
        public CarbonImmutable $dari,
        public CarbonImmutable $sampai,
        public ?string $channel = null,
        public ?string $kampanye = null,
        public ?string $industri = null,
        public ?string $landing = null,
        public ?string $perangkat = null,
        public ?string $paket = null,
        public ?string $programReferral = null,
        /** Penyaring partner menunggu FASE 38.09; disimpan agar bentuk filternya tidak berubah kelak. */
        public ?string $partner = null,
    ) {}

    /** @param array<string, mixed> $kueri */
    public static function dariKueri(array $kueri): self
    {
        $sampai = self::tanggal($kueri['sampai'] ?? null) ?? CarbonImmutable::now()->endOfDay();
        $dari = self::tanggal($kueri['dari'] ?? null) ?? $sampai->subDays(29)->startOfDay();

        return new self(
            dari: $dari->startOfDay(),
            sampai: $sampai->endOfDay(),
            channel: self::teks($kueri['channel'] ?? null),
            kampanye: self::teks($kueri['kampanye'] ?? null),
            industri: self::teks($kueri['industri'] ?? null),
            landing: self::teks($kueri['landing'] ?? null),
            perangkat: self::teks($kueri['perangkat'] ?? null),
            paket: self::teks($kueri['paket'] ?? null),
            programReferral: self::teks($kueri['referral'] ?? null),
            partner: self::teks($kueri['partner'] ?? null),
        );
    }

    /** Apakah ada penyaring selain rentang tanggal; rentang selalu terisi. */
    public function menyaringPengunjung(): bool
    {
        return $this->channel !== null
            || $this->kampanye !== null
            || $this->industri !== null
            || $this->landing !== null
            || $this->perangkat !== null
            || $this->paket !== null
            || $this->programReferral !== null;
    }

    /** @return array<string, string|null> */
    public function keArray(): array
    {
        return [
            'dari' => $this->dari->toDateString(),
            'sampai' => $this->sampai->toDateString(),
            'channel' => $this->channel,
            'kampanye' => $this->kampanye,
            'industri' => $this->industri,
            'landing' => $this->landing,
            'perangkat' => $this->perangkat,
            'paket' => $this->paket,
            'referral' => $this->programReferral,
            'partner' => $this->partner,
        ];
    }

    private static function tanggal(mixed $nilai): ?CarbonImmutable
    {
        if (! is_string($nilai) || trim($nilai) === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($nilai);
        } catch (\Throwable) {
            return null;
        }
    }

    private static function teks(mixed $nilai): ?string
    {
        if (! is_string($nilai)) {
            return null;
        }

        $bersih = trim($nilai);

        return $bersih === '' ? null : $bersih;
    }
}
