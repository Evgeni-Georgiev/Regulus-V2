<?php

namespace App\Enums;

trait EnumsTrait
{
    public static function values(): array
    {
        return array_column(self::cases(), 'value', 'value');
    }

    public static function localizedValues(): array
    {
        return array_column(self::cases(), 'label');
    }
}
