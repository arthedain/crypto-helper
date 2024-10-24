<?php

namespace App\Enums;

enum CryptoExchangeEnum: string
{
    case Binance = 'Binance';
    case Bybit = 'Bybit';
    case Jbex = 'Jbex';
    case Poloniex = 'Poloniex';
    case Whitebit = 'Whitebit';
}
