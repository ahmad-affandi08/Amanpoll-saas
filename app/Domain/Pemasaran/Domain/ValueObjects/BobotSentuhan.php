<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Domain\ValueObjects;

/**
 * Bobot satu sentuhan, disimpan sebagai bilangan bulat berbasis SKALA. Jumlah
 * bobot seluruh sentuhan satu konversi karena itu tepat satu menurut
 * konstruksinya, bukan menurut pembulatan (Gate 38.10).
 */
final readonly class BobotSentuhan
{
    /** Satu jasa penuh dinyatakan sebagai sejuta bagian. */
    public const SKALA = 1_000_000;

    public function __construct(
        public Sentuhan $sentuhan,
        public int $bagian,
    ) {}

    public function pecahan(): float
    {
        return $this->bagian / self::SKALA;
    }

    public function porsiDari(float $nilai): float
    {
        return $nilai * $this->bagian / self::SKALA;
    }

    /** @return array<string, mixed> */
    public function keArray(): array
    {
        return [
            ...$this->sentuhan->keArray(),
            'Bagian' => $this->bagian,
            'Bobot' => $this->pecahan(),
        ];
    }
}
