<?php

declare(strict_types=1);

namespace App\Domain\Langganan\Domain\ValueObjects;

use Carbon\CarbonImmutable;

/**
 * Jawaban penyedia saat pembayaran dimulai (PRD 8.23): alamat halaman bayar gateway
 * tempat pengguna dialihkan, atau rincian yang ditampilkan (transfer manual).
 */
final readonly class InstruksiPembayaran
{
    /** @param  array<string, mixed>  $rincian */
    private function __construct(
        public ?string $urlPembayaran,
        public ?CarbonImmutable $kedaluwarsaPada,
        public ?string $referensiPenyedia,
        public array $rincian,
    ) {}

    public static function pengalihan(
        string $urlPembayaran,
        ?CarbonImmutable $kedaluwarsaPada = null,
        ?string $referensiPenyedia = null,
    ): self {
        return new self($urlPembayaran, $kedaluwarsaPada, $referensiPenyedia, []);
    }

    /** @param  array<string, mixed>  $rincian */
    public static function rincian(array $rincian): self
    {
        return new self(null, null, null, $rincian);
    }

    public function mengalihkan(): bool
    {
        return $this->urlPembayaran !== null;
    }
}
