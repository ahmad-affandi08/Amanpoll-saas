<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Domain\Enums;

enum ChannelKampanye: string
{
    case GoogleAds = 'GoogleAds';
    case MetaAds = 'MetaAds';
    case LinkedIn = 'LinkedIn';
    case TikTok = 'TikTok';
    case YouTube = 'YouTube';
    case Email = 'Email';
    case WhatsApp = 'WhatsApp';
    case Organic = 'Organic';
    case Referral = 'Referral';
    case Partner = 'Partner';
    case Direct = 'Direct';
    case Custom = 'Custom';
}
