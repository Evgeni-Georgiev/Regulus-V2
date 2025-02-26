<?php

namespace App\Enums;

enum TransactionTypeEnum: string
{
    use EnumsTrait;

    case BUY = 'buy';
    case SELL = 'sell';
}
