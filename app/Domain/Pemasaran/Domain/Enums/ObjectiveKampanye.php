<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Domain\Enums;

enum ObjectiveKampanye: string
{
    case Traffic = 'Traffic';
    case Lead = 'Lead';
    case Demo = 'Demo';
    case Trial = 'Trial';
    case Activation = 'Activation';
    case Subscription = 'Subscription';
    case Retention = 'Retention';
    case Referral = 'Referral';
}
