<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case GCash = 'gcash';
    case Maya = 'maya';
    case Bdo = 'bdo';
    case Bpi = 'bpi';
    case Other = 'other';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
