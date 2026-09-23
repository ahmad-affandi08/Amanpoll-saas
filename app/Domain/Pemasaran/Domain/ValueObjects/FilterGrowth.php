<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Domain\ValueObjects;

use App\Core\Organisasi\KalenderOrganisasi;
use Carbon\CarbonImmutable;

/**
 * Sembilan penyaring dashboard growth (MARKETING.md 5).
 *
 * `dari` dan `sampai` adalah momen UTC batas hari di kalender vendor (zona
 * bawaan), siap untuk kolom waktu berjam. Kolom `date` memakai
 * `tanggalDari()`/`tanggalSampai()`.
 */
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
        /** Kode partner pengirim lead; menyaring pengunjung lewat prospek yang ditautkan lead itu. */
        public ?string $partner = null,
    ) {}

    /** @param array<string, mixed> $kueri */
    public static function dariKueri(array $kueri): self
    {
        $zona = KalenderOrganisasi::zonaBawaan();
        $sampai = self::tanggal($kueri['sampai'] ?? null, $zona) ?? CarbonImmutable::now($zona);
        $dari = self::tanggal($kueri['dari'] ?? null, $zona) ?? $sampai->subDays(29);

        return new self(
            dari: $dari->startOfDay()->utc(),
            sampai: $sampai->endOfDay()->utc(),
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
            || $this->programReferral !== null
            || $this->partner !== null;
    }

    /** Tanggal kalender hari pertama rentang (Y-m-d), untuk kolom `date`. */
    public function tanggalDari(): string
    {
        return $this->dari->setTimezone(KalenderOrganisasi::zonaBawaan())->toDateString();
    }

    /** Tanggal kalender hari terakhir rentang (Y-m-d), untuk kolom `date`. */
    public function tanggalSampai(): string
    {
        return $this->sampai->setTimezone(KalenderOrganisasi::zonaBawaan())->toDateString();
    }

    /** @return array<string, string|null> */
    public function keArray(): array
    {
        return [
            'dari' => $this->tanggalDari(),
            'sampai' => $this->tanggalSampai(),
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

    private static function tanggal(mixed $nilai, string $zona): ?CarbonImmutable
    {
        if (! is_string($nilai) || trim($nilai) === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse(substr(trim($nilai), 0, 10), $zona);
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
