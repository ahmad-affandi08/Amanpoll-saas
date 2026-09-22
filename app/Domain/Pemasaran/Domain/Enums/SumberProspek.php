<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Domain\Enums;

/** Asal prospek (MARKETING.md 5.1). */
enum SumberProspek: string
{
    case Website = 'Website';
    case Demo = 'Demo';
    case Trial = 'Trial';
    case Email = 'Email';
    case WhatsApp = 'WhatsApp';
    case Referral = 'Referral';
    case Partner = 'Partner';
    case Social = 'Social';
    case OrganicSearch = 'OrganicSearch';
    case Kampanye = 'Kampanye';
    case ImporCsv = 'ImporCsv';
    case Api = 'Api';
    case Webhook = 'Webhook';
    case LeadMagnet = 'LeadMagnet';
    case Manual = 'Manual';
}
