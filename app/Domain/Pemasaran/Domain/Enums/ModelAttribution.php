<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Domain\Enums;

/**
 * Model pembagian jasa antar sentuhan. First dan last touch tetap bawaan;
 * model lain berdiri di sampingnya, tidak menggantikannya (MARKETING.md 14).
 */
enum ModelAttribution: string
{
    case Pertama = 'Pertama';
    case Terakhir = 'Terakhir';
    case Linear = 'Linear';
    case TimeDecay = 'TimeDecay';
    case PositionBased = 'PositionBased';

    public function label(): string
    {
        return match ($this) {
            self::Pertama => 'Sentuhan pertama',
            self::Terakhir => 'Sentuhan terakhir',
            self::Linear => 'Linear',
            self::TimeDecay => 'Peluruhan waktu',
            self::PositionBased => 'Berbasis posisi',
        };
    }

    public function keterangan(): string
    {
        return match ($this) {
            self::Pertama => 'Seluruh jasa diberikan kepada sentuhan pertama.',
            self::Terakhir => 'Seluruh jasa diberikan kepada sentuhan terakhir.',
            self::Linear => 'Jasa dibagi rata kepada seluruh sentuhan.',
            self::TimeDecay => 'Sentuhan yang lebih dekat ke konversi mendapat porsi lebih besar.',
            self::PositionBased => 'Sentuhan pertama dan terakhir masing-masing 40 persen, sisanya dibagi rata.',
        };
    }

    /** Model satu sentuhan tidak perlu membaca seluruh perjalanan pengunjung. */
    public function satuSentuhan(): bool
    {
        return $this === self::Pertama || $this === self::Terakhir;
    }

    /** Hanya peluruhan waktu yang membaca jarak sentuhan ke konversinya. */
    public function memakaiParuhWaktu(): bool
    {
        return $this === self::TimeDecay;
    }
}
