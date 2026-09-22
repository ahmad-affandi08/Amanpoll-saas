<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Domain\Enums;

/** Jenis template email pemasaran (MARKETING.md 15). */
enum JenisTemplateEmail: string
{
    case Nurturing = 'Nurturing';
    case Onboarding = 'Onboarding';
    case Trial = 'Trial';
    case Lifecycle = 'Lifecycle';
    case Reactivation = 'Reactivation';
    case Newsletter = 'Newsletter';
    case Referral = 'Referral';
    case Partner = 'Partner';
}
