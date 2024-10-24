<?php

namespace App\Services\Api;

use App\Dto\TradingPairDTO;
use Illuminate\Support\Collection;

interface ExchangeApiContract
{
    public function getName(): string;

    public function getTradingPairs(): Collection;

    public function checkTradingPair(string $firstCurrency, string $secondCurrency): bool;

    public function getTradingPair(string $firstCurrency, string $secondCurrency): null|TradingPairDTO;
}
