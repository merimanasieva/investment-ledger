<?php

namespace App\Enums;

enum MovementType: string
{
    case DEPOSIT = 'deposit';
    case WITHDRAWAL = 'withdrawal';
    case BUY = 'buy';
    case SELL = 'sell';
}