<?php

namespace App\Enums;

enum StockMovementTypeEnum
{
    case IN;
    case OUT;

    public function label(): string
    {
        return match ($this) {
            self::IN => 'In',
            self::OUT => 'Out',
        };
    }

    public function value(): string
    {
        return match ($this) {
            self::IN => 'in',
            self::OUT => 'out',
        };
    }
}
