<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Domain\Enums;

/** Tipe halaman pemasaran (MARKETING.md 8). */
enum TipeHalamanPemasaran: string
{
    case General = 'General';
    case Industri = 'Industri';
    case Fitur = 'Fitur';
    case UseCase = 'UseCase';
    case Campaign = 'Campaign';
    case Comparison = 'Comparison';
    case LeadMagnet = 'LeadMagnet';
    case Pricing = 'Pricing';
    case Partner = 'Partner';
    case Referral = 'Referral';
}
